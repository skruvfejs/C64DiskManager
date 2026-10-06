<?php

declare(strict_types=1);

class C64PetsciiRenderer
{
    private string $rom;

    public function __construct(string $romPath)
    {
        $rom = file_get_contents($romPath);

        if ($rom === false || strlen($rom) !== 4096) {
            throw new RuntimeException(
                'Invalid C64 character ROM.'
            );
        }

        $this->rom = $rom;
    }

    public function render(string $data, int $scale = 4): string
    {
        $width = 8 * $scale;
        $height = 8 * $scale;

        $chars = [];

        for ($i = 0; $i < strlen($data); $i++) {
            $petscii = ord($data[$i]);

            /*
             * Directory-art uses the shifted PETSCII graphics
             * range. C1-CDF map to screen codes 61-7F.
             */
            if ($petscii >= 0xC1 && $petscii <= 0xDF) {
                $screenCode = $petscii - 0x60;
            } else {
                $screenCode = $petscii;
            }

            $chars[] = $screenCode;
        }

        $rows = intdiv(count($chars) + 15, 16);

        $svgWidth = 16 * $width;
        $svgHeight = $rows * $height;

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" ' .
            'width="%d" height="%d" viewBox="0 0 %d %d">',
            $svgWidth,
            $svgHeight,
            $svgWidth,
            $svgHeight
        );

        $svg .= '<rect width="100%" height="100%" fill="#352879"/>';

        foreach ($chars as $index => $screenCode) {
            if ($screenCode > 255) {
                continue;
            }

            $charX = ($index % 16) * $width;
            $charY = intdiv($index, 16) * $height;

            $romOffset = $screenCode * 8;

            for ($row = 0; $row < 8; $row++) {
                $bits = ord($this->rom[$romOffset + $row]);

                for ($col = 0; $col < 8; $col++) {
                    if (($bits & (0x80 >> $col)) === 0) {
                        continue;
                    }

                    $x = $charX + ($col * $scale);
                    $y = $charY + ($row * $scale);

                    $svg .= sprintf(
                        '<rect x="%d" y="%d" width="%d" height="%d" fill="#6c5eb5"/>',
                        $x,
                        $y,
                        $scale,
                        $scale
                    );
                }
            }
        }

        $svg .= '</svg>';

        return $svg;
    }
}
