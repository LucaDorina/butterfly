<?php declare(strict_types=1);

namespace App\Package\Document;

Use PhpOffice\PhpSpreadsheet\Reader\Xlsx as Reader;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class XlsxDocument
{
    /**
     * @var string
     */
    private $filename;

    public function __construct(string $filename)
    {
        if (!realpath($filename)) {
            throw new \Exception("File $filename is not exists");
        }

        $this->filename = $filename;
    }

    public function readColumns(): array
    {
        $worksheet = $this->getWorksheet();
        $ri = 3;

        $pRange = "A$ri:" . $worksheet->getHighestColumn() . $ri;

        $data = $worksheet->rangeToArray($pRange);

        return $data[0] ?? [];
    }

    private function tree(array $data)
    {
        $cols = count($data[0] ?? []);

        $matrix = [];
        $lvl1 = $lvl2 = $lvl3 = null;
        for ($i = 0; $i < $cols; $i++) {
            $a = &$data[0][$i];
            if (is_null($a)) {
                if (is_null($lvl1)) {
                    throw new \Exception;
                }
                $a = $lvl1;
            } else {
                $lvl2 = $lvl1 = $a;
            }

            $b = &$data[0][$i];
            if (is_null($b)) {
                $b = $lvl2;
            } else {
                $lvl2 = $b;
            }

            $c = $data[2][$i];
            if (!is_null($c)) {
                $matrix[$a][$b][] = $c;
            }
        }

        return $matrix;
    }

    public function read()
    {
        $worksheet = $this->getWorksheet();

        return $worksheet->toArray();
    }

    public function getWorksheet(): Worksheet
    {
        $reader = new Reader();

        if (!$reader->canRead($this->filename)) {
            throw new \Exception('Something went wrong');
        }

        $spreadsheet = $reader->load($this->filename);

        return $spreadsheet->getSheetByName('full_data');
    }
}