<?php
# TODO: class명 정해야함. 일단 임시로 사용.
namespace Saseul\Constant;

class MongoDbConfig
{
    public const DB_TRACKER = 'saseul_tracker';
    public const DB_COMMITTED = 'saseul_committed';

    public const NAMESPACE_TRACKER = 'saseul_tracker.tracker';
    public const NAMESPACE_BLOCK = 'saseul_committed.blocks';
    public const NAMESPACE_TRANSACTION = 'saseul_committed.transactions';
    public const NAMESPACE_GENERATION = 'saseul_committed.generations';

    public const COLLECTION_TRACKER = 'tracker';
}
