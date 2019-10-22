<?php

namespace Saseul\Constant;

// Todo: Change name.
class Directory
{
    public const BLOCKDATA = SASEUL_DIR . '/blockdata';
    public const SOURCE = SASEUL_DIR . '/sourcedata';
    public const RELATIVE_SOURCE = '/sourcedata';
    public const TEMP = SASEUL_DIR . '/tmp';

    // Todo: delete.
    //  Reset Script 에서만 사용한다.
    public const RELATIVE_ORIGINAL_SOURCE = '..' . self::RELATIVE_SOURCE . '/SaseulDefault';

    public const API_CHUNKS = self::BLOCKDATA . '/apichunks';
    public const BROADCAST_CHUNKS = self::BLOCKDATA . '/broadcastchunks';
    public const TRANSACTIONS = self::BLOCKDATA . '/transactions';
    public const TX_ARCHIVE = self::BLOCKDATA . '/txarchives';
    public const GENERATIONS = self::BLOCKDATA . '/generations';

    public const TMP_BUNCH = self::TEMP . '/bunch.tar.gz';
    public const TMP_SOURCE = self::TEMP . '/source.tar.gz';

    // Source 관리에 대한 const
    public const SASEUL_SOURCE = SASEUL_DIR . '/src/Saseul';
    public const TAR_SOURCE_DIR = SASEUL_DIR . '/data/core';
    public const SOURCE_PREFIX = 'Saseul-';

    public const PID_FILE = SASEUL_DIR . '/data/core/saseuld.pid';
}
