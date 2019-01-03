<?php
/**
 * Created by PhpStorm.
 * User: dawidjez
 * Date: 21/12/2018
 * Time: 23:15
 */

namespace Engine\Error\ShiftError;


final class ErrorHighlighter {

    public $highlighted = '';

    /*
     * @var StackTrace
     */

    public function __construct(string $file, int $lineWithError = null, bool $hidden = false) {
        $this->setCodeStyle();
        $this->highlighted = $this->highlight_file_with_line_numbers($file, $lineWithError, $hidden);
    }

    /**
     * @param string $file
     * @param int $lineWithError
     * @param bool $hidden
     * @return string
     */
    protected final function highlight_file_with_line_numbers(string $file, int $lineWithError = null, bool $hidden = false): string {
        $code = substr(highlight_file($file, true), 36, -15);
        $lines = explode('<br />', $code);
        $lineCount = count($lines);
        $padLength = strlen($lineCount);
        $additionalStyle = $hidden ? 'style="display: none;"' : '';
        $highlighted = '<div class="file-presentation" id="' . md5($file . $lineWithError) . '" ' . $additionalStyle . '>';
        if ($lineWithError)
            $highlighted .= '<div id="yellow-line" style="top: 75px"></div>';

        $highlighted .= "<code><span style=\"color: #000000\">";

        $start = null;
        $limit = 10;
        if ($lineWithError) {
            $start = $lineWithError - 5;
        }

        foreach ($lines as $i => $line) {
            if ($start) {
                if ($start > $i + 1) {
                    continue;
                }
                if ($start + $limit < $i + 1) {
                    continue;
                }
            }
            $lineNumber = str_pad($i + 1, $padLength, " ", STR_PAD_LEFT);
            $highlighted .= '<span style="color: #999999">' . $lineNumber . ' | </span>' . $line . "\n";
        }

        $highlighted .= "</span></code></div>";


        return $highlighted;
    }

    /**
     * @param array $trace
     * @return StackTrace
     */
    public final function getBeautyStackTrace(array $trace): StackTrace {
        $stackTrace = new StackTrace();
        $traceItems = '';
        $traceItemsCode = '';
        foreach ($trace as $key => $info) {
            $infoFile = isset($info['file']) ? $info['file'] : (isset($trace[$key + 1]) ? $trace[$key + 1]['file'] : '');
            $pathAsArray = explode('/', $infoFile);
            $filename = $pathAsArray[count($pathAsArray) - 1];

            $fargs = '';

            foreach ($info['args'] as $arg) {
                $type = gettype($arg);

                switch ($type) {
                    case 'string':
                        if (strlen($arg) === 0) {
                            $fargs .= "''";
                        } else {
                            $fargs .= "'" . $arg . "'";
                        }
                        break;
                    case 'array':
                        $fargs .= '<span class="array">Array<span class="array-content">' . print_r($arg, true) . '</span></span>';
                        break;
                    case 'object':
                        $fargs .= '<span class="array">' . get_class($arg) . ' Object<span class="array-content">' . print_r($arg, true) . '</span></span>';
                        break;
                    default:
                        $fargs .= $arg;
                }
                $fargs .= ', ';
            }
            $fargs = trim($fargs, ', ');

            $infoLine = isset($info['line']) ? $info['line'] : (isset($trace[$key + 1]) ? $trace[$key + 1]['line'] : '');
            $traceItemsCode .= strlen($infoFile) && stream_is_local($infoFile) ? $this->highlight_file_with_line_numbers($infoFile, (int)$infoLine, true) : '';
            $traceItems .= '
            <tr class="trace-item" data-id="' . md5($infoFile . $infoLine) . '" data-f="' . $infoFile . '" data-l="' . $infoLine . '">
                <td>
                ' . $infoLine . '
                </td>
                <td>
                ' . $filename . '
                </td>
                <td class="method">
                ' . $info["class"] . $info["type"] . $info["function"] . '(' . $fargs . ')
                </td >
            </tr >
            ';
        }
        $stackTrace->traceItems = $traceItems;
        $stackTrace->traceItemsCode = $traceItemsCode;
        return $stackTrace;
    }

    protected final function setCodeStyle(): void {
        ini_set('highlight.string', '#a3dd00;');
        ini_set('highlight.comment', '#636363;');
        ini_set('highlight.keyword', '#C07041;');
        ini_set('highlight.default', '#798aA0;');
        ini_set('highlight.html', '#000000;');
    }
}
