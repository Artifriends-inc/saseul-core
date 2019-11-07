<?php

namespace Saseul\Constant;

use Saseul\Util\Mongo;

class MongoDb
{
    public const ASC = 1;
    public const DESC = -1;

    public const NAMESPACE_ATTRIBUTE = Mongo::DB_COMMITTED . '.' . Mongo::COLLECTION_ATTRIBUTES;
    public const NAMESPACE_BLOCK = Mongo::DB_COMMITTED . '.' . Mongo::COLLECTION_BLOCKS;
    public const NAMESPACE_COIN = Mongo::DB_COMMITTED . '.' . Mongo::COLLECTION_COIN;
    public const NAMESPACE_CONTRACT = Mongo::DB_COMMITTED . '.' . Mongo::COLLECTION_CONTRACT;
    public const NAMESPACE_TRANSACTION = Mongo::DB_COMMITTED . '.' . Mongo::COLLECTION_TRANSACTIONS;
    public const NAMESPACE_TOKEN = Mongo::DB_COMMITTED . '.' . Mongo::COLLECTION_TOKEN;
    public const NAMESPACE_TOKEN_LIST = Mongo::DB_COMMITTED . '.' . Mongo::COLLECTION_TOKEN_LIST;
    public const NAMESPACE_GENERATION = Mongo::DB_COMMITTED . '.' . Mongo::COLLECTION_GENERATIONS;
    public const NAMESPACE_TRACKER = Mongo::DB_TRACKER . '.' . Mongo::COLLECTION_TRACKER;
}
