<?php
/**
 * Created by PhpStorm.
 * User: dawid
 * Date: 10.07.18
 * Time: 22:08
 */

namespace View;


use Engine\Request;

/**
 * Class ViewBuilder
 * @package View
 */
class ViewBuilder
{

    /**
     * @var string
     */
    private $html;
    /**
     * @var array
     */
    private $styles = [];
    /**
     * @var array
     */
    private $scripts = [];

    /**
     * ViewBuilder constructor.
     */
    public function __construct(string $html)
    {
        $this->html = $html;
    }

    /**
     * @param string $title
     * @return ViewBuilder
     */
    public function setTitle(string $title): self
    {
        $this->html = str_replace('{{ $title }}', $title, $this->html);
        return $this;
    }

    /**
     * @return string
     */
    public function getView(): string
    {
        return $this->html;
    }

    /**
     * @return ViewBuilder
     */
    public function build(): self
    {
        $this->buildStyles();
        $this->buildScripts();
        $this->replacePHPClosures();
        $this->replacePHPCode();
        $this->replacePHPVariables();
        return $this;
    }

    /**
     * @return ViewBuilder
     */
    private function buildStyles(): self
    {
        $styleDestination = '/public/css/' . Request::getController() . '/' . Request::getAction() . '.css';
        $styles = '<link rel="stylesheet" href="' . $styleDestination . '"/>';

        foreach ($this->styles as $style) {
            $addStyle = true;
            if (!is_url($style)) {
                $addStyle = file_exists(PUBLIC_PATH . '/css/' . $styles);
            }
            if ($addStyle) {
                $styles .= '<link rel="stylesheet" href="/public/css/' . $style . '"/>';
            }
        }
        $this->html = str_replace('{{ $styles }}', $styles, $this->html);

        return $this;
    }

    /**
     * @return ViewBuilder
     */
    private function buildScripts(): self
    {
        $scriptsDestination = '/public/js/' . Request::getController() . '/' . Request::getAction() . '.js';
        $scripts = '<script src="' . $scriptsDestination . '"></script>';

        foreach ($this->scripts as $script) {
            $addScript = true;
            if (!is_url($script)) {
                $addScript = file_exists(PUBLIC_PATH . '/js/' . $script);
            }
            if ($addScript) {
                $scripts .= '<script src="/public/js/' . $script . '"></script>';
            }
        }
        $this->html = str_replace('{{ $scripts }}', $scripts, $this->html);
        return $this;
    }

    /**
     *
     */
    private function replacePHPClosures(): void
    {
        $this->html = str_replace(
            ['@endif', '@endfor', '@endforeach', '@endwhile', '@endphp'],
            ['<?php endif; ?>', '<?php endfor; ?>', '<?php endforeach; ?>', '<?php endwhile; ?>', '<?php endphp; ?>'],
            $this->html
        );
    }

    /**
     *
     */
    private function replacePHPCode(): void
    {
        $this->html = preg_replace_callback(
            '/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x', function ($match) {
            return $this->compileStatement($match);
        }, $this->html);
    }

    /**
     * @param array $match
     * @return string
     */
    private function compileStatement(array $match): string
    {
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

    /**
     *
     */
    private function replacePHPVariables(): void
    {
        $pattern = '/{{ \$(\w+) }}/';
        $replcement = '<?= __($$1); ?>';
        $this->html = preg_replace($pattern, $replcement, $this->html);
    }

    /**
     * @return array
     */
    public function getStyles(): array
    {
        return $this->styles;
    }

    /**
     * @param array $styles
     * @return ViewBuilder
     */
    public function setStyles(array $styles): self
    {
        $this->styles = $styles;
        return $this;
    }

    /**
     * @return array
     */
    public function getScripts(): array
    {
        return $this->scripts;
    }

    /**
     * @param array $scripts
     * @return ViewBuilder
     */
    public function setScripts(array $scripts): self
    {
        $this->scripts = $scripts;
        return $this;
    }
}
