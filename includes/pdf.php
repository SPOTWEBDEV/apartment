<?php
declare(strict_types=1);
/** Minimal single-page PDF writer: Helvetica text, rectangles, lines and one JPEG. No libraries needed. */
final class SimplePdf {
  public const W = 595.28, H = 841.89;
  private array $ops = [];
  private ?array $img = null;
  private string $title = '';
  // Helvetica advance widths for ASCII 32..126 (per 1000 units)
  private const R = [278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584];
  private const B = [278,333,474,556,556,889,722,238,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,333,333,584,584,584,611,975,722,722,722,722,667,611,778,722,278,556,722,611,833,722,778,667,778,722,667,611,722,667,944,667,667,611,333,278,333,584,556,333,556,611,556,611,556,333,611,611,278,278,556,278,889,611,611,611,611,389,556,333,611,556,778,556,556,500,389,280,389,584];

  public function title(string $t): void { $this->title = $t; }
  private static function enc(string $s): string { $c = @mb_convert_encoding($s, 'Windows-1252', 'UTF-8'); return $c === false ? $s : $c; }
  private static function esc(string $s): string { return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], self::enc($s)); }
  private static function col(array $c): string { return sprintf('%.3F %.3F %.3F', $c[0] / 255, $c[1] / 255, $c[2] / 255); }

  public function width(string $s, float $size, bool $bold = false): float {
    $t = $bold ? self::B : self::R; $w = 0;
    foreach (str_split(self::enc($s)) as $ch) { $o = ord($ch); $w += ($o >= 32 && $o <= 126) ? $t[$o - 32] : 556; }
    return $w * $size / 1000;
  }
  /** y is measured from the top of the page. $align: l, r or c (x is then the right edge or centre). */
  public function text(float $x, float $y, string $s, float $size = 10, bool $bold = false, array $rgb = [11,42,67], string $align = 'l'): void {
    $w = $this->width($s, $size, $bold);
    if ($align === 'r') $x -= $w; elseif ($align === 'c') $x -= $w / 2;
    $this->ops[] = sprintf('BT /F%d %.2F Tf %s rg %.2F %.2F Td (%s) Tj ET', $bold ? 2 : 1, $size, self::col($rgb), $x, self::H - $y, self::esc($s));
  }
  public function rect(float $x, float $y, float $w, float $h, array $rgb): void {
    $this->ops[] = sprintf('%s rg %.2F %.2F %.2F %.2F re f', self::col($rgb), $x, self::H - $y - $h, $w, $h);
  }
  public function line(float $x1, float $y1, float $x2, float $y2, array $rgb = [226,232,240], float $wd = 0.7): void {
    $this->ops[] = sprintf('q %.2F w %s RG %.2F %.2F m %.2F %.2F l S Q', $wd, self::col($rgb), $x1, self::H - $y1, $x2, self::H - $y2);
  }
  public function wrap(string $s, float $maxW, float $size, bool $bold = false): array {
    $lines = []; $cur = '';
    foreach (preg_split('/\s+/', trim($s)) as $word) {
      $try = $cur === '' ? $word : "$cur $word";
      if ($cur !== '' && $this->width($try, $size, $bold) > $maxW) { $lines[] = $cur; $cur = $word; } else $cur = $try;
    }
    if ($cur !== '') $lines[] = $cur;
    return $lines;
  }
  public function image(string $jpegPath, float $x, float $y, float $w): void {
    $info = @getimagesize($jpegPath);
    if (!$info || $info[2] !== IMAGETYPE_JPEG) return;
    $h = $w * $info[1] / $info[0];
    $this->img = ['data' => (string)file_get_contents($jpegPath), 'w' => $info[0], 'h' => $info[1]];
    $this->ops[] = sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /Im1 Do Q', $w, $h, $x, self::H - $y - $h);
  }

  public function output(): string {
    $objs = [
      1 => '<< /Type /Catalog /Pages 2 0 R >>',
      2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
    ];
    $stream = implode("\n", $this->ops);
    $objs[4] = '<< /Length ' . strlen($stream) . " >>\nstream\n$stream\nendstream";
    $objs[5] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
    $objs[6] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
    $n = 6; $xo = '';
    if ($this->img) {
      $id = ++$n; $xo = " /XObject << /Im1 $id 0 R >>";
      $objs[$id] = sprintf("<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length %d >>\nstream\n", $this->img['w'], $this->img['h'], strlen($this->img['data'])) . $this->img['data'] . "\nendstream";
    }
    $objs[3] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 5 0 R /F2 6 0 R >>%s >> /Contents 4 0 R >>', self::W, self::H, $xo);
    $info = ++$n; $objs[$info] = '<< /Title (' . self::esc($this->title) . ') /Producer (Booking receipt) >>';
    ksort($objs);
    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $off = [];
    foreach ($objs as $i => $body) { $off[$i] = strlen($pdf); $pdf .= "$i 0 obj\n$body\nendobj\n"; }
    $x = strlen($pdf);
    $pdf .= "xref\n0 " . ($n + 1) . "\n0000000000 65535 f \n";
    for ($i = 1; $i <= $n; $i++) $pdf .= sprintf("%010d 00000 n \n", $off[$i]);
    return $pdf . "trailer\n<< /Size " . ($n + 1) . " /Root 1 0 R /Info $info 0 R >>\nstartxref\n$x\n%%EOF";
  }
}
