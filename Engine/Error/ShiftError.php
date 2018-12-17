<?php
/**
 * Created by PhpStorm.
 * User: dawid
 * Date: 07.07.18
 * Time: 17:58
 */

namespace Engine\Error;


use Throwable;

/**
 * Class ShiftError
 * @package Engine\Error
 */
class ShiftError extends \Error {

    /**
     * ShiftError constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);

        $line = (int)$this->getLine();
        $highlighted = $this->highlight_file_with_line_numbers($this->getFile(), $line);
        $stackTrace = $this->getBeautyStackTrace();

        echo '
        <style>
        *{margin: 0;padding: 0;}
        #error-container{width: 100%;float: left;background: #135171;box-sizing: border-box;padding: 5px;color:#fff;font-family: Arial, sans-serif}
        #error-container #message{width: 100%;float: left;}
        #error-container .file-presentation,
        #error-container #stack-trace .trace-item .file-presentation{white-space: pre-wrap;background: white;position: relative}
        #error-container #stack-trace .trace-item .file-presentation{display: none;}
        #error-container .file-presentation #yellow-line,
        #error-container #stack-trace .trace-item  .file-presentation #yellow-line{width: 100%;z-index: 1;position: absolute;height: 15px;left: 0;background: rgba(255,255,0,0.36)}
        #error-container .file-presentation code,
        #error-container #stack-trace .trace-item .file-presentation code{z-index: 2;}
        #error-container #stack-trace .trace-item{border-bottom:1px #ffffff solid;padding: 5px 2px}
        #error-container #stack-trace .trace-item:last-child{border:none;}
        #error-container #stack-trace .trace-item .filename{font-weight: bold}
        #error-container #stack-trace .trace-item .filepath{font-size: 12px;line-height: 15px;}
        #error-container #stack-trace .trace-item .filepath .show-hide{cursor: pointer;font-size: 15px;padding: 0 5px;font-weight: bold;}
        #error-container #stack-trace .trace-item .method{font-size: 14px;font-style: italic}
        #error-container #stack-trace .trace-item .method .array{border-bottom: 1px #fff dotted; cursor: pointer;position: relative}
        #error-container #stack-trace .trace-item .method .array .array-content{display: none;position: absolute;bottom: 15px;left: -70px;background: #f3f3f3;color: #000;min-width: 200px;padding: 4px;white-space: pre;border: 1px #a2a2a2 dotted;cursor: text;font-style: normal}
        #error-container #stack-trace .trace-item .method .array:hover .array-content{display: inline}
        </style>
        <div id="error-container">
            <div id="message">' . $this->getMessage() . '</div>
            <div id="file-info">in <b>' . $this->getFile() . '</b></div>
            <div class="file-presentation">' . $highlighted . '</div>
            <br><br>
            <div id="stack-trace-text">Stack trace:</div>
            <div id="stack-trace">' . $stackTrace . '</div>
        </div>
        <script>
        const buttons = document.getElementsByClassName("show-hide");
        
        for(const button of buttons) {
          button.addEventListener("click", function(e) {
            const filePresentation = this.parentElement.parentElement.getElementsByClassName("file-presentation");

            if(!filePresentation.length) {
              return;
            }

            if (filePresentation[0].style.display !== "block") {
              filePresentation[0].style.display = "block";
              this.innerText = "-";
            } else {
              this.innerText = "+";
              filePresentation[0].style.display = "none";
            }
          });
        }
        </script>';
    }

    /**
     * @param string $file
     * @param int $lineWithError
     * @return string
     */
    private function highlight_file_with_line_numbers(string $file, int $lineWithError = null): string {
        $code = substr(highlight_file($file, true), 36, -15);
        $lines = explode('<br />', $code);
        $lineCount = count($lines);
        $padLength = strlen($lineCount);

        $return = '';
        if ($lineWithError)
            $return .= '<div id="yellow-line" style="top: 75px"></div>';

        $return .= "<code><span style=\"color: #000000\">";

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
            $return .= '<span style="color: #999999">' . $lineNumber . ' | </span>' . $line . "\n";
        }

        $return .= "</span></code>";


        return $return;
    }

    /**
     * @return string
     */
    private function getBeautyStackTrace(): string {
        $return = '';
        foreach ($this->getTrace() as $info) {
            $infoFile = isset($info['file']) ? $info['file'] : '';
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

            $infoLine = isset($info['line']) ? $info['line'] : '';
            $filePresentation = strlen($infoFile) && stream_is_local($infoFile) ? $this->highlight_file_with_line_numbers($infoFile, (int)$infoLine) : '';
            $return .= '
            <div class="trace-item">
                <div class="filename">
                ' . $filename . ':' . $infoLine . '
                </div>
                <div class="filepath">
                ' . $info["file"] . ' <span class="show-hide">+</span>
                </div>
                <div class="method">
                ' . $info["class"] . $info["type"] . $info["function"] . '(' . $fargs . ')
                </div >
                <div class="file-presentation">' . $filePresentation . '</div >
            </div >
            ';
        }
        return $return;
    }
}
