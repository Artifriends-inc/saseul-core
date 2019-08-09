<?php

namespace Saseul\Util;

class File
{
    public static function rrmdir($dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            if (is_dir("{$dir}/{$file}")) {
                self::rrmdir("{$dir}/{$file}");
            } else {
                unlink("{$dir}/{$file}");
            }
        }

        return rmdir($dir);
    }

    /**
     * dir 에 있는 모든 파일을 가져온다.
     *
     * @param string $dir
     *
     * @return array
     */
    public static function getAllfiles(string $dir): array
    {
        $files = [];

        if (is_dir($dir)) {
            $contents = glob($dir . '/*');

            foreach ($contents as $item) {
                if (is_file($item)) {
                    $files[] = $item;
                }

                if (is_dir($item)) {
                    // Todo: 잘못하면 무한 재귀가 된다.
                    $files = array_merge($files, self::getAllfiles($item));
                }
            }
        }

        sort($files);

        return $files;
    }
}
