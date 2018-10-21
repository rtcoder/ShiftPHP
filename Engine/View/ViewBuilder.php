<?php
/**
 * Created by PhpStorm.
 * User: dawid
 * Date: 10.07.18
 * Time: 22:08
 */

namespace View;


use Engine\Request;

class ViewBuilder {

    private $html;

    /**
     * ViewBuilder constructor.
     */
    public function __construct(string $html) {
        $this->html = $html;
    }


    public function setScripts(): self {
        $scriptsDestination = '/public/js/' . Request::getController() . '/' . Request::getAction() . '.js';

        $this->html = str_replace('{{ $scripts }}', '<script src="' . $scriptsDestination . '"></script>', $this->html);

        return $this;
    }

    public function setStyles(): self {
        $scriptsDestination = '/public//css/' . Request::getController() . '/' . Request::getAction() . '.css';

        $this->html = str_replace('{{ $styles }}', '<link rel="stylesheet" href="' . $scriptsDestination . '"/>', $this->html);

        return $this;
    }

    public function setTitle(string $title): self {
        $this->html = str_replace('{{ $title }}', $title, $this->html);
    }

    public function getView(): string {
        return $this->html;
    }


    public function build(): self {
        $this->replacePHPClosures();
        $this->replacePHPCode();
        $this->replacePHPVariables();
        return $this;
    }

    private function replacePHPCode(): void {
        $this->html = preg_replace_callback(
            '/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x', function ($match) {
            return $this->compileStatement($match);
        }, $this->html);
    }

    private function replacePHPClosures(): void {
        $this->html = str_replace(
            ['@endif', '@endfor', '@endforeach', '@endwhile', '@endphp'],
            ['<?php endif; ?>', '<?php endfor; ?>', '<?php endforeach; ?>', '<?php endwhile; ?>', '<?php endphp; ?>'],
            $this->html
        );
    }

    private function replacePHPVariables(): void {
        $pattern = '/{{ \$(\w+) }}/';
        $replcement = '<?= __($$1); ?>';
        $this->html = preg_replace($pattern, $replcement, $this->html);
    }

    private function compileStatement(array $match): string {
        switch ($match[1]) {
            case 'include':
                return '<?php include ' . $match[4] . ';?>';
                break;
            case 'if':
                return '<?php if' . $match[3] . ':?>';
                break;
            case 'for':
                return '<?php for' . $match[3] . ':?>';
                break;
            case 'foreach':
                return '<?php foreach' . $match[3] . ':?>';
                break;
            case 'while':
                return '<?php while' . $match[3] . ':?>';
                break;
            default:
                return '';
        }
    }
}