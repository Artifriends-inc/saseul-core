from pathlib import Path
from shutil import rmtree

from invoke import Collection, task

from cli.docker import down as docker_down

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


# 위에다가 task를 입력하고 아래에는 사용할 명령어 추가
ns = Collection()
ns.add_task(clean)
