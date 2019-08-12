from pathlib import Path
from shutil import rmtree

from invoke import Collection, call, task
from pymongo import ASCENDING, DESCENDING, IndexModel, MongoClient
from pymongo.collection import Collection as MongoCollection
from pymongo.database import Database as MongoDatabase

from cli.docker import (
    down as docker_down,
    up as docker_up,
)

REPO_ROOT = Path.cwd()


@task(pre=[docker_down])
def clean(ctx):
    """Block data 와 DB 데이터를 삭제한다."""
    api_chunks_dir = REPO_ROOT / 'blockdata/apichunks'
    broadcast_chunks_dir = REPO_ROOT / 'blockdata/broadcastchunks'
    transaction_dir = REPO_ROOT / 'blockdata/transactions'
    tx_archives_dir = REPO_ROOT / 'blockdata/txarchives'
    generations_dir = REPO_ROOT / 'blockdata/generations'
    db_dir = REPO_ROOT / 'data/db'

    for dir_path in (api_chunks_dir, broadcast_chunks_dir, transaction_dir,
                     tx_archives_dir, generations_dir, db_dir):
        rm_dir(dir_path)
        dir_path.mkdir(parents=True)

    print('Reset data dir')

    # touch keep file
    for path in (api_chunks_dir, broadcast_chunks_dir, transaction_dir,
                 tx_archives_dir, generations_dir):
        keep = path / '.keep'
        keep.touch()

    print('Make keep files')


def rm_dir(dir_path: Path) -> None:
    try:
        rmtree(dir_path)
    except FileNotFoundError as e:
        print(e.args[1], dir_path)


@task(pre=[call(docker_up, service='mongo')])
def mk_mongo_db(ctx):
    """MongoDB 데이터베이스와 인덱스를 생성한다."""
    client = MongoClient('localhost', 27017)
    mk_mongo_committed_db(client)
    mk_mongo_tracker_db(client)


def mk_mongo_committed_db(client: MongoClient):
    """Committed database index 를 생성한다."""
    db = MongoDatabase(client, 'saseul_committed')

    MongoCollection(db, 'blocks').create_indexes([
        IndexModel([('timestamp', ASCENDING)], name='timestamp_asc'),
        IndexModel([('timestamp', DESCENDING)], name='timestamp_desc'),
        IndexModel([('block_number', ASCENDING)], name='block_number_asc'),
    ])

    MongoCollection(db, 'transactions').create_indexes([
        IndexModel([('timestamp', ASCENDING)], name='timestamp_asc'),
        IndexModel([('timestamp', DESCENDING)], name='timestamp_desc'),
        IndexModel([('timestamp', ASCENDING), ('thash', ASCENDING)],
                   name='timestamp_thash_asc'),
        IndexModel([('thash', ASCENDING), ('timestamp', ASCENDING)],
                   name='thash_timestamp_unique', unique=True),
    ])

    MongoCollection(db, 'generations').create_indexes([
        IndexModel([('origin_block_number', ASCENDING)],
                   name='origin_block_number_unique', unique=True),
    ])

    MongoCollection(db, 'coin').create_indexes([
        IndexModel([('address', ASCENDING)],
                   name='address_unique', unique=True),
    ])

    MongoCollection(db, 'attributes').create_indexes([
        IndexModel([('address', ASCENDING), ('key', ASCENDING)],
                   name='address_unique', unique=True),
    ])

    MongoCollection(db, 'contract').create_indexes([
        IndexModel([('clid', ASCENDING)], name='cid_asc'),
        IndexModel([('chash', ASCENDING)], name='chash_asc'),
        IndexModel([('timestamp', ASCENDING)], name='timestamp_asc'),
        IndexModel([('timestamp', DESCENDING)], name='timestamp_desc'),
        IndexModel([('timestamp', ASCENDING), ('chash', ASCENDING)],
                   name='timestamp_chash_asc'),
    ])

    MongoCollection(db, 'token').create_indexes([
        IndexModel([('address', ASCENDING)], name='address_asc'),
        IndexModel([('token_name', ASCENDING)], name='token_name_asc'),
        IndexModel([('address', ASCENDING), ('token_name', ASCENDING)],
                   name='address_token_name_asc', unique=True),
    ])

    MongoCollection(db, 'token_list').create_indexes([
        IndexModel([('token_name', ASCENDING)],
                   name='token_name_asc', unique=True),
    ])

    print('Create saseul_committed Databases on MongoDB')


def mk_mongo_tracker_db(client: MongoClient):
    """Tracker database index 를 생성한다."""
    db = MongoDatabase(client, 'saseul_tracker')
    MongoCollection(db, 'tracker').create_indexes([
        IndexModel([('address', ASCENDING)],
                   name='address_unique', unique=True),
    ])

    print('Create saseul_tracker Databases on MongoDB')


@task(pre=[clean, mk_mongo_db])
def reset(ctx):
    """SASEUL 블록 정보들을 전부 재 생성한다."""
    print('SASEUL node reset')


# 위에다가 task를 입력하고 아래에는 사용할 명령어 추가
ns = Collection()
ns.add_task(clean)
ns.add_task(mk_mongo_db)
ns.add_task(reset)
