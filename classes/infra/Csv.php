<?php

/**
 * Copyright (c) Christoph M. Becker
 *
 * This file is part of Twocents_XH.
 *
 * Twocents_XH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Twocents_XH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Twocents_XH.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Twocents\Infra;

class Csv
{
    /** @return ?list<list<string>> */
    public static function recordsFromString(
        string $contents,
        string $separator = ",",
        string $enclosure = '"'
    ): ?array {
        $stream = fopen("php://memory", "w+");
        if ($stream === false) {
            return null;
        }
        if (fwrite($stream, $contents) !== strlen($contents)) {
            fclose($stream);
            return null;
        }
        if (!rewind($stream)) {
            fclose($stream);
            return null;
        }
        $res = [];
        while (($record = fgetcsv($stream, 0, $separator, $enclosure, "\0")) !== false) {
            if (!self::validateRecord($record)) {
                continue;
            }
            $res[] = $record;
        }
        fclose($stream);
        return $res;
    }

    /**
     * @param ?list<?string> $record
     * @phpstan-assert-if-true list<string> $record
     */
    private static function validateRecord(?array $record): bool
    {
        if ($record === null) {
            return false;
        }
        foreach ($record as $field) {
            if (!is_string($field)) {
                return false;
            }
        }
        return true;
    }

    /** @param list<list<string>> $records */
    public static function stringFromRecords(
        array $records,
        string $separator = ",",
        string $enclosure = '"'
    ): ?string {
        $stream = fopen("php://memory", "w+");
        if ($stream === false) {
            return null;
        }
        foreach ($records as $record) {
            if (!fputcsv($stream, $record, $separator, $enclosure, "\0")) {
                fclose($stream);
                return null;
            }
        }
        if (!rewind($stream)) {
            fclose($stream);
            return null;
        }
        $res = (string) stream_get_contents($stream);
        fclose($stream);
        return $res;
    }
}
