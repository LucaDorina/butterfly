<?php declare(strict_types=1);

namespace App\Package\Document;

use Cocur\Arff;

class Converter
{
    public function getColumns(string $file): array
    {
        $doc = new XlsxDocument($file);

        $columns = $this->unique(
            $this->filterTitles(
                $doc->readColumns()
            )
        );

        return $columns;
    }

    private function filterTitles(array $titles)
    {
        return array_filter($titles, function ($value) {
            return !is_null($value);
        });
    }

    public function xlsxToArff(string $file, string $relation, array $columns): string
    {
        $doc = new XlsxDocument($file);

        $data = $doc->read();

        $titles = $data[2];
        $titles = $this->unique($this->filterTitles($titles));
        $data = array_slice($data, 6);

        $arff = new Arff\Document($this->getColumnName($relation));

        $names = [];
        foreach ($columns as $index) {
            $name = $titles[$index];
            $name = $this->getColumnName($name);
            $names[$index] = $name;

            $arff->addColumn($this->suggestColumnType($name, $data, $index));
        }

        foreach ($data as $row) {
            $items = [];
            foreach ($names as $index => $name) {
                $items[$name] = $row[$index];
            }
            if ($this->isRowValid($items)) {
                $arff->addData($items);
            }
        }

        $writer = new Arff\Writer();
        return $writer->render($arff);
    }

    protected function isRowValid(array $row): bool
    {
        foreach ($row as $cell) {
            if (is_null($cell)) {
                return false;
            }
        }
        return true;
    }

    protected function getColumnName(string $name): string
    {
        $name = trim($name);
        $name = str_replace(['{', '}', ',', '%', ' '], '_', $name);
        return $name;
    }

    protected function suggestColumnType($name, array $data, int $column): Arff\Column\ColumnInterface
    {
        if (empty($data)) {
            throw new \Exception('Could not suggest column type. Data is empty');
        }

        $max = 2;
        foreach ($data as $row) {
            $cell = $row[$column];

            if (is_string($cell)) {
                return new Arff\Column\StringColumn($name);
            }

            if (is_numeric($cell)) {
                if ($cell == (int)$cell . '.0') {
                    $cell = (int)$cell;
                }
                if (is_int($cell) && $cell > 0 && $cell < 10) {
                    if ($max < $cell) {
                        $max = $cell;
                    }
                    continue;
                }
                return new Arff\Column\NumericColumn($name);
            }

            if (empty($cell)) {
                continue;
            }
            throw new \Exception('Could not suggest column type');
        }

        return new Arff\Column\NominalColumn($name, range(1, $max));
    }



    private function unique(array $row)
    {
//        foreach ($row as $i => $a) {
//            $t = 0;
//            for ($j = $i + 1; $j < count($row); $j++) {
//                if ($a === $row[$j]) {
//                    $row[$j] .= '_' . ++$t;
//                }
//            }
//        }
        foreach ($row as $k => $v) {
            $row[$k] = trim($v) . $k;
        }
        return $row;
    }
}