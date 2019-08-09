<?php

namespace Saseul\Constant;

class Directory
{
    public const BLOCKDATA = SASEUL_DIR . '/blockdata';
    public const SOURCE = SASEUL_DIR . '/sourcedata';
    public const RELATIVE_SOURCE = '/sourcedata';
    public const TEMP = SASEUL_DIR . '/tmp';

    // Reset Script 에서만 사용한다.
    public const RELATIVE_ORIGINAL_SOURCE = '..' . self::RELATIVE_SOURCE . '/SaseulDefault';
    public const SASEUL_SOURCE = SASEUL_DIR . '/src/Saseul';

    public const NODE_INFO = SASEUL_DIR . '/node.info';

    public const API_CHUNKS = self::BLOCKDATA . '/apichunks';
    public const BROADCAST_CHUNKS = self::BLOCKDATA . '/broadcastchunks';
    public const TRANSACTIONS = self::BLOCKDATA . '/transactions';
    public const TX_ARCHIVE = self::BLOCKDATA . '/txarchives';
    public const GENERATIONS = self::BLOCKDATA . '/generations';

    public const TMP_BUNCH = self::TEMP . '/bunch.tar.gz';
    public const TMP_SOURCE = self::TEMP . '/source.tar.gz';

    public const SOURCE_PREFIX = 'Saseul';
}
