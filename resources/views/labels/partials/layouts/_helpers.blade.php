@php
    if (! function_exists('label_sticker_font_size')) {
        function label_sticker_font_size($text, $baseSize, $minSize = 8, $charBudget = 28)
        {
            $len = mb_strlen((string) $text);
            if ($len <= $charBudget) {
                return (int) $baseSize;
            }

            return max((int) $minSize, (int) floor($baseSize * $charBudget / $len));
        }
    }
@endphp
