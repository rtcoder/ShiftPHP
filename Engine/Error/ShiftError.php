<?php
/**
 * Created by PhpStorm.
 * User: dawid
 * Date: 07.07.18
 * Time: 17:58
 */

namespace Engine\Error;


use Engine\Error\ShiftError\ErrorHighlighter;
use Throwable;

/**
 * Class ShiftError
 * @package Engine\Error
 */
class ShiftError extends \Error
{

    /**
     * ShiftError constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $line = (int)$this->getLine();
        $errorHighlighter = new ErrorHighlighter($this->getFile(), $line);
        $highlighted = $errorHighlighter->highlighted;
        $stackTrace = $errorHighlighter->getBeautyStackTrace($this->getTrace());

        echo '
        <link href="https://fonts.googleapis.com/css?family=Roboto:100" rel="stylesheet">
        <style>
        *{margin: 0;padding: 0;-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;}
        #error-container{width: 100vw; height: 100vh;float: left;background: #111;box-sizing: border-box;padding: 5px;color:#fff;font-family: "Roboto", sans-serif;font-weight: 100;}
        #error-container #message{width: 100%;float: left;font-size: 42px;color: #f0f0f0;}
        #error-container .file-presentation{white-space: pre-wrap;background: #131313;position: relative;min-height: 165px;}
        #error-container .file-presentation #yellow-line{width: 100%;z-index: 1;position: absolute;height: 15px;left: 0;background: rgba(132, 132, 132, 0.22)}
        #error-container .file-presentation code{z-index: 2;}
        #error-container #stack-trace{color: #ffffff;width: 100%;border-collapse: collapse;}
        #error-container #stack-trace .trace-item{padding: 5px 0;cursor: pointer;}
        #error-container #stack-trace .trace-item:hover{background: rgba(255, 0, 0, 0.3);}
        #error-container #stack-trace .trace-item td{border: none}
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
            <div id="file-info"><b>' . $this->getFile() . ': ' . $line . '</b></div>
            ' . $highlighted . $stackTrace->traceItemsCode . '
            <br><br>
            <div id="stack-trace-text">Stack trace:</div>
            <table id="stack-trace" border="0">
            <tbody>
            <tr class="trace-item" data-id="' . md5($this->getFile() . $line) . '">
                <td>
                ' . $line . '
                </td>
                <td>
                ' . str_replace(APP_ROOT . '/', '', $this->getFile()) . '
                </td>
                <td class="method">
                </td >
            </tr >
            ' . $stackTrace->traceItems . '
            </tbody>
            </table>
        </div>
        <script>
        const buttons = document.getElementsByClassName("trace-item");
        
        for(const button of buttons) {
          button.addEventListener("click", function(e) {
            const id = this.getAttribute("data-id")
            const filePresentationList = document.getElementsByClassName("file-presentation");
            const targetFilePresentation = document.getElementById(id);
            
            if (!filePresentationList.length || !targetFilePresentation) {
              return;
            }
            
            for (let node of filePresentationList) {
              node.style.display = "none";
            }

            targetFilePresentation.style.display = "block";
          });
        }
        </script>';
    }

}
