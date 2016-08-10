<?php
/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Multi-type Export Handler
 */
namespace Bossanova\Export;

use Bossanova\Database\Database;

class Export
{
    // Report Headers (array of names)
    public static $header;

    // Database instance
    public $instance;

    // Default separator for CSV files
    public $separator = ',';

    // Default author
    public $author = '';

    // Record index controller
    private $counter = 0;

    /**
     * Generate reports in different data types
     * @Param object $result - array, resource or reference
     * @Param string $type - Report type must be: xml, pdf, xls, csv, json or html
     * @Param string $title - Report title
     * @Param array $header - Report headers
     * @Param string $separator - Report separators, used in CSV files
     *
     * @TODO: Change the variable $result to receive a reference from memory or direct PDO object to avoid duplicated data loaded in memory.
     */
    public function run($result, $type = 'html', $title = 'Untitled', $header = NULL, $separator = ',')
    {
        // Define the global headers
        if (isset($header)) {
            $this->header = $header;
        }

        // Define the global separator in case CSV files
        $this->separator = $separator;

        // If the method exists call it.
        if (method_exists($this, $type)) {
            $this->{$type}($title, $result);
        }
    }

    /**
     * Generate a XML report based on a provided data or reference
     * @Param title $result - Report title
     * @Result object $result - array, resource or reference
     */
    public function xml($title, $result)
    {
        // Mime type
        header("Content-type:text/xml\r\n");

        // Today
        $today = date("Y-m-d H:i:s");

        echo "<report title='$title' data='$today'>";

        if (count(self::$header)) {
            echo "<header>";

            foreach (self::$header as $k => $v) {
                echo "<column>$v</column>";
            }

            echo "</header>";

            $header = 1;
        }

        echo "<body>";

        while ($row = $this->fetch($result)) {
            if (! isset($header)) {
                echo "<header>";

                foreach ($row as $k => $v)
                    echo "<column>" . ucwords(str_replace("_", " ", $k)) . "</column>";

                echo "</header>";

                $header = 1;
            }

            echo "<record>";

            foreach ($row as $k => $v)
                echo "<column>$v</column>";

            echo "</record>";
        }

        echo "</body>";

        echo "</report>";
    }

    /**
     * Generate a JSON report based on a provided data or reference
     * @Param title $result - Report title
     * @Result object $result - array, resource or reference
     */
    public function json($title, $result)
    {
        // Mime type
        header("Content-type:text/json\r\n");

        // Today
        $today = date("Y-m-d H:i:s");

        // Values
        $content['title'] = $title;
        $content['date'] = $today;

        $i = 0;

        if (count(self::$header)) {
            foreach (self::$header as $k => $v) {
                $content['header'][$i] = $v;

                $i ++;
            }

            $header = 1;
        }

        $i = 0;
        $j = 0;

        while ($row = $this->fetch($result)) {
            if (! isset($header)) {
                foreach ($row as $k => $v) {
                    $content['header'][$i] = ucwords(str_replace("_", " ", $k));

                    $i ++;
                }

                $header = 1;
            }

            $i = 0;

            foreach ($row as $k => $v) {
                $content['content'][$j][$i] = $v;

                $i ++;
            }

            $j ++;
        }

        echo json_encode($content);
    }

    /**
     * Generate a CSV report based on a provided data or reference
     * @Param title $result - Report title
     * @Result object $result - array, resource or reference
     */
    public function csv($title, $result)
    {
        header("Content-type:text/csv, charset=UTF-8; encoding=UTF-8\r\n");
        header("Content-Disposition: attachment; filename=$title.csv");

        if (count(self::$header)) {
            foreach (self::$header as $k => $v) {
                echo utf8_decode($v) . $this->separator;
            }

            echo "\n";

            $header = 1;
        }

        while ($row = $this->fetch($result)) {
            if (! isset($header)) {
                foreach ($row as $k => $v) {
                    echo ucwords(str_replace("_", " ", utf8_decode($k))) . $this->separator;
                }

                echo "\n";

                $header = 1;
            }

            foreach ($row as $k => $v) {
                echo utf8_decode($v) . $this->separator;
            }

            echo "\n";
        }
    }

    /**
     * Generate a XSLT report based on a provided data or reference
     * @Param title $result - Report title
     * @Result object $result - array, resource or reference
     */
    public function xslt($title, $result)
    {
        require_once 'components/phpexcel/PHPExcel.php';

        $objPHPExcel = new PHPExcel();

        $objPHPExcel->getProperties()
            ->setCreator($this->author)
            ->setTitle($title)
            ->setSubject($title)
            ->setDescription($title);

        // Counters
        $i = 0;
        $j = 1;

        if (count(self::$header)) {
            foreach (self::$header as $k => $v) {
                $column = PHPExcel_Cell::stringFromColumnIndex($i) . $j;
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue($column, utf8_decode($v));
                $i ++;
            }

            $header = 1;
            $j ++;
        }

        $i = 0;

        while ($row = $this->fetch($result)) {
            if (! isset($header)) {
                foreach ($row as $k => $v) {
                    $column = PHPExcel_Cell::stringFromColumnIndex($i) . $j;
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($column, ucwords(str_replace("_", " ", utf8_decode($k))));
                    $i ++;
                }

                $header = 1;
                $j ++;
            }

            $i = 0;

            foreach ($row as $k => $v) {
                $column = PHPExcel_Cell::stringFromColumnIndex($i) . $j;

                if (is_numeric($v)) {
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit($column, utf8_decode($v), PHPExcel_Cell_DataType::TYPE_NUMERIC);
                } else
                    if (is_numeric(str_replace('%', '', $v))) {
                        $objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit($column, utf8_decode($v) / 100, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $objPHPExcel->getActiveSheet()
                            ->getStyle($column)
                            ->getNumberFormat()
                            ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00);
                    } else {
                        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($column, utf8_decode($v));
                    }

                $i ++;
            }

            $j ++;
        }

        // Add some data
        $title = str_replace("\n", "", $title) . '.xls';

        // Redirect output to a clientâ€™s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$title");
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * Generate a simple XSL file report based on a provided data or reference
     * @Param title $result - Report title
     * @Result object $result - array, resource or reference
     */
    public function xsl($title, $result)
    {
        $title = str_replace(" ", "_", $title);

        header("Pragma: public");
        header("Expires:0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/ms-excel; charset=UTF-8LE");
        header("Content-Disposition: attachment;filename=$title.xls");
        header("Content-Transfer-Encoding: binary");

        $this->xlsBOF();

        $i = 0;

        if (count(self::$header)) {
            foreach (self::$header as $k => $v) {
                $this->xlsWriteLabel(0, $i, utf8_decode($v));

                $i ++;
            }

            $header = 1;
        }

        $i = 0;
        $j = 1;

        while ($row = $this->fetch($result)) {
            if (! isset($header)) {
                foreach ($row as $k => $v) {
                    $this->xlsWriteLabel(0, $i, ucwords(str_replace("_", " ", utf8_decode($k))));

                    $i ++;
                }

                $header = 1;
            }

            $i = 0;

            foreach ($row as $k => $v) {
                if (is_numeric($v))
                    $this->xlsWriteNumber($j, $i, utf8_decode($v));
                else {
                    $this->xlsWriteLabel($j, $i, utf8_decode($v));
                }

                $i ++;
            }

            $j ++;
        }

        $this->xlsEOF();
    }

    /**
     * Generate a simple HTML table report based on a provided data or reference
     * @Param title $result - Report title
     * @Result object $result - array, resource or reference
     */
    public function pdf($title, $result)
    {
        require_once 'components/fpdf/fpdf.php';

        $size = array();

        $pdf = new FPDF();
        $pdf->AddPage("L", "A3");
        $pdf->SetFont('Arial', 'B', 5);

        if (count(self::$header)) {
            $i = 0;

            foreach (self::$header as $k => $v) {
                if (! isset($size[$i]))
                    $size[$i] = 0;

                if ($size[$i] < strlen($v))
                    $size[$i] = strlen($v);

                $i ++;
            }
        }

        $i = 0;

        while ($row = $this->fetch($result)) {
            if (! isset($header)) {
                foreach ($row as $k => $v) {
                    if (! isset($size[$i]))
                        $size[$i] = 0;

                    if ($size[$i] < strlen($k))
                        $size[$i] = strlen($k);

                    $i ++;
                }

                $header = 1;
            }

            $i = 0;

            foreach ($row as $k => $v) {
                if (! isset($size[$i]))
                    $size[$i] = 0;

                if ($size[$i] < strlen($row[$k]))
                    $size[$i] = strlen($row[$k]);

                $i ++;
            }

            $rows[] = $row;
        }

        $i = 0;

        $header = NULL;

        if (count(self::$header)) {
            foreach (self::$header as $k => $v) {
                $pdf->Cell($size[$i] + 1, 3, utf8_decode($v), 1);

                $i ++;
            }

            $pdf->Ln();

            $header = 1;
        }

        $i = 0;

        $counter = 0;

        if (isset($rows)) {
            foreach ($rows as $index => $row) {
                if (! isset($header)) {
                    foreach ($row as $k => $v) {
                        if (! isset($size[$i]))
                            $size[$i] = 0;

                        $pdf->Cell($size[$i] + 1, 3, ucwords(str_replace("_", " ", utf8_decode($k))), 1);

                        $i ++;
                    }

                    $pdf->Ln();

                    $header = 1;
                }

                $i = 0;

                foreach ($row as $k => $v) {
                    $pdf->Cell($size[$i] + 1, 3, utf8_decode($v), 1);

                    $i ++;
                }

                $pdf->Ln();
            }
        }

        $pdf->Output();
    }

    /**
     * Generate a simple HTML table report based on a provided data or reference
     * @Param title $result - Report title
     * @Result object $result - array, resource or reference
     */
    public function table($title, $result)
    {
        echo "<table border='0' cellpadding='2' cellspacing='0' id='report_table' style='clear:both; border-collapse:collapse;'>\n";

        if (count(self::$header)) {
            echo "<thead><tr>";

            foreach (self::$header as $k => $v) {
                echo "<th><b>$v</b></th>";
            }

            echo "</tr></thead>\n<tbody>\n";

            $header = 1;
        }

        while ($row = $this->fetch($result)) {
            if (! isset($header)) {
                echo "<thead><tr>\n";

                foreach ($row as $k => $v)
                    echo "<th>" . ucwords(str_replace("_", " ", $k)) . "</th>";

                echo "</tr></thead>\n<tbody>\n";

                $header = 1;
            }

            echo "<tr>";

            foreach ($row as $k => $v)
                echo "<td>$v</td>";

            echo "</tr>\n";
        }

        echo "</tbody>\n</table>\n";
    }

    /**
     * Generate a HTML report based on a provided data or reference
     * @Param title $result - Report title
     * @Result object $result - array, resource or reference
     */
    public function html($title, $result)
    {
        // Today
        $today = date("Y-m-d H:i:s");

        echo "<html><meta http-equiv='Content-Type' content='text/html;charset=utf-8' ></html><body><style>div { float:left;margin-bottom:10px; font:9px Verdana; } td, th { padding:4px; border-collapse:collapse; border:1px solid black; font:9px Verdana; }</style>";
        echo "<div style='width:4000px;'></div><div><img src=''></div><div style='padding:10px;padding-left:40px;'><span style='font:16px Verdana;color:#5f92a2;'>$title</span><br><span style='font:9px Verdana'>$today</span></div>";
        echo "<table border='0' cellpadding='2' cellspacing='0' id='conteudo' style='clear:both; border-collapse:collapse;'>\n";

        if (count(self::$header)) {
            echo "<thead><tr>";

            foreach (self::$header as $k => $v) {
                echo "<th><b>$v</b></th>";
            }

            echo "</tr></thead>\n<tbody>\n";

            $header = 1;
        }

        while ($row = $this->fetch($result)) {
            if (! isset($header)) {
                echo "<thead><tr>\n";

                foreach ($row as $k => $v)
                    echo "<th>" . ucwords(str_replace("_", " ", $k)) . "</th>";

                echo "</tr></thead>\n<tbody>\n";

                $header = 1;
            }

            echo "<tr>";

            foreach ($row as $k => $v)
                echo "<td>$v</td>";

            echo "</tr>\n";
        }

        echo "</tbody>\n</table></body></html>\n";
    }

    /**
     * Return the record based on the type.
     * This function is important to keep the unifed way to code.
     * @Param object $result - resource or reference
     * @Result array $row - array of contents
     */
    function fetch($result)
    {
        $row = array();

        if ($result instanceof stdclass) {
            if (isset($result->GetDataGeneralResult->Result->DataLevels->DataLevel->Records->DataRecord[$this->counter])) {
                $row = $result->GetDataGeneralResult->Result->DataLevels->DataLevel->Records->DataRecord[$this->counter]->Values->anyType;
            }
        } else
            if (is_array($result)) {
                if (isset($result[$this->counter])) {
                    $row = $result[$this->counter];
                }
            } else {
                $query = Database::getInstance($this->instance);
                $row = $query->fetch_assoc($result, 1);
            }

        if (! count($row))
            $this->counter = 0;
        else {
            $this->counter ++;
        }

        return $row;
    }

    /**
     * Function return the header extrated from a object
     */
    function setHeader($result)
    {
        self::$header = NULL;

        $row = array();

        if ($result instanceof stdclass) {
            foreach ($result->GetDataGeneralResult->Result->DataLevels->DataLevel->Variables->Variable as $k => $v) {
                $row[$k] = $v->Name;
            }
        }

        self::$header = $row;
    }

    function setInstance($instance)
    {
        $this->instance = $instance;
    }

    /**
     * Support function to generate simple xsl files
     */
    function xlsBOF()
    {
        echo pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0);
        return;
    }

    /**
     * Support function to generate simple xsl files
     */
    function xlsEOF()
    {
        echo pack("ss", 0x0A, 0x00);
        return;
    }

    /**
     * Support function to generate simple xsl files
     */
    function xlsWriteNumber($Row, $Col, $Value)
    {
        echo pack("sssss", 0x203, 14, $Row, $Col, 0x0);
        echo pack("d", $Value);
        return;
    }

    /**
     * Support function to generate simple xsl files
     */
    function xlsWriteLabel($Row, $Col, $Value)
    {
        $L = strlen($Value);
        echo pack("ssssss", 0x204, 8 + $L, $Row, $Col, 0x0, $L);
        echo $Value;
        return;
    }
}