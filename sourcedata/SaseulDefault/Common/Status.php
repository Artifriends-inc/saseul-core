<?php

namespace Saseul\Common;

interface Status
{
    /**
     * Status 값을 초기화한다.
     */
    public static function _reset(): void;

    /**
     * 저장되어 있는 Status 값을 읽어온다.
     */
    public static function _load(): void;

    /**
     * Status 값을 전처리한다.
     */
    public static function _preprocess(): void;

    /**
     * Status 값을 저장한다.
     */
    public static function _save(): void;

    /**
     * Status 값을 후처리한다.
     */
    public static function _postprocess(): void;
}
