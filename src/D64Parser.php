<?php

declare(strict_types=1);

class D64Parser
{
    private const TRACK_SECTORS = [
        1 => 21,  2 => 21,  3 => 21,  4 => 21,  5 => 21,
        6 => 21,  7 => 21,  8 => 21,  9 => 21, 10 => 21,
       11 => 21, 12 => 21, 13 => 21, 14 => 21, 15 => 21,
       16 => 21, 17 => 21,

       18 => 19, 19 => 19, 20 => 19, 21 => 19, 22 => 19,
       23 => 19, 24 => 19,

       25 => 18, 26 => 18, 27 => 18, 28 => 18, 29 => 18,
       30 => 18,

       31 => 17, 32 => 17, 33 => 17, 34 => 17, 35 => 17,
    ];


    public function __construct(private string $data)
    {
    }

    public function parse(): array
    {
        $size = strlen($this->data);

        if (!in_array($size, [174848, 175531], true)) {
            throw new RuntimeException(
                "Unsupported D64 size: {$size} bytes"
            );
        }

        return $this->readDirectory();
    }

    private function readDirectory(): array
    {
        $files = [];
	$directoryArt = [];
        $errors = [];

        $track = 18;
        $sector = 1;
        $guard = 0;

        while ($track !== 0) {
            if (++$guard > 100) {
                $errors[] = 'Invalid directory chain.';
                break;
            }

            if (!isset(self::TRACK_SECTORS[$track])) {
                $errors[] = "Invalid directory track {$track}.";
                break;
            }

            if ($sector < 0 || $sector >= self::TRACK_SECTORS[$track]) {
                $errors[] =
                    "Invalid directory sector {$sector} on track {$track}.";
                break;
            }

            $offset = $this->sectorOffset($track, $sector);

            if ($offset + 256 > strlen($this->data)) {
                $errors[] = "Directory sector is outside the image.";
                break;
            }

            $nextTrack = ord($this->data[$offset]);
            $nextSector = ord($this->data[$offset + 1]);

            for ($entry = 0; $entry < 8; $entry++) {
                $pos = $offset + 2 + ($entry * 32);

$fileTypeByte = ord($this->data[$pos]);

/*
 * C0 directory entries are used by some disks for
 * directory artwork. Preserve the raw 16 PETSCII
 * bytes instead of treating the entry as a DEL file.
 */
if (
    $fileTypeByte === 0xC0 &&
    ord($this->data[$pos + 1]) === 0x12 &&
    ord($this->data[$pos + 2]) === 0x00
) {
    $directoryArt[] = substr($this->data, $pos + 3, 16);
    continue;
}

$filename = $this->readPetAscii(
    substr($this->data, $pos + 3, 16)
);

$blocks =
    ord($this->data[$pos + 28]) |
    (ord($this->data[$pos + 29]) << 8);

$type = $fileTypeByte & 0x07;

$types = [
    0 => 'DEL',
    1 => 'SEQ',
    2 => 'PRG',
    3 => 'USR',
    4 => 'REL',
];

$files[] = [
    'filename' => $filename,
    'file_type' => $types[$type] ?? '???',
    'blocks' => $blocks,
    'locked' => ($fileTypeByte & 0x40) !== 0,
    'closed' => ($fileTypeByte & 0x80) !== 0,
    'start_track' => ord($this->data[$pos + 1]),
    'start_sector' => ord($this->data[$pos + 2]),
];




            }

            /*
             * Track/sector 0 means end of directory.
             *
             * A corrupt disk can contain an invalid next-sector pointer.
             * Do not reject the whole disk. Record the error and keep
             * everything we managed to read.
             */
            if ($nextTrack === 0) {
                break;
            }

            if (!isset(self::TRACK_SECTORS[$nextTrack])) {
                $errors[] =
                    "Invalid directory track {$nextTrack}.";

                break;
            }

            if (
                $nextSector < 0 ||
                $nextSector >= self::TRACK_SECTORS[$nextTrack]
            ) {
                $errors[] =
                    "Invalid directory sector {$nextSector} " .
                    "on track {$nextTrack}.";

                break;
            }

            $track = $nextTrack;
            $sector = $nextSector;
        }

return [
    'files' => $files,
    'directory_art' => $directoryArt,
    'errors' => $errors,
];
    }

    private function sectorOffset(int $track, int $sector): int
    {
        $offset = 0;

        for ($t = 1; $t < $track; $t++) {
            $offset += self::TRACK_SECTORS[$t] * 256;
        }

        return $offset + ($sector * 256);
    }

    private function readPetAscii(string $data): string
    {
        $result = '';

        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $byte = ord($data[$i]);

            if ($byte === 0x20 || $byte === 0xA0) {
                $result .= ' ';
            } elseif ($byte >= 0x41 && $byte <= 0x5A) {
                $result .= chr($byte);
            } elseif ($byte >= 0x61 && $byte <= 0x7A) {
                $result .= chr($byte);
            } elseif ($byte >= 0x30 && $byte <= 0x39) {
                $result .= chr($byte);
            } elseif ($byte >= 0xC1 && $byte <= 0xDA) {
                // PETSCII shifted A-Z
                $result .= chr($byte - 0x80);
            } elseif ($byte >= 0x21 && $byte <= 0x2F) {
                $result .= chr($byte);
            } elseif ($byte >= 0x3A && $byte <= 0x40) {
                $result .= chr($byte);
            } else {
                $result .= '.';
            }
       }
    return rtrim($result);
}



}
