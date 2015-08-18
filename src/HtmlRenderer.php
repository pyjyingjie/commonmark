<?php

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * Original code based on the CommonMark JS reference parser (http://bitly.com/commonmark-js)
 *  - (c) John MacFarlane
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace League\CommonMark;

use League\CommonMark\Block\Element\BlockElement;
use League\CommonMark\Inline\Element\InlineElement;

/**
 * Renders a parsed AST to HTML
 */
class HtmlRenderer implements ElementRendererInterface
{
    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @param Environment $environment
     */
    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * @param string $option
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getOption($option, $default = null)
    {
        return $this->environment->getConfig('renderer/' . $option, $default);
    }

    /**
     * @param string $string
     * @param bool   $preserveEntities
     *
     * @return string
     */
    public function escape($string, $preserveEntities = false)
    {
        if ($preserveEntities) {
            $string = preg_replace('/[&](?![#](x[a-f0-9]{1,8}|[0-9]{1,8});|[a-z][a-z0-9]{1,31};)/i', '&amp;', $string);
        } else {
            $string = str_replace('&', '&amp;', $string);
        }

        return str_replace(['<', '>', '"'], ['&lt;', '&gt;', '&quot;'], $string);
    }

    /**
     * @param InlineElement $inline
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    protected function renderInline(InlineElement $inline)
    {
        $renderer = $this->environment->getInlineRendererForClass(get_class($inline));
        if (!$renderer) {
            throw new \RuntimeException('Unable to find corresponding renderer for inline type ' . get_class($inline));
        }

        return $renderer->render($inline, $this);
    }

    /**
     * @param InlineElement[] $inlines
     *
     * @return string
     */
    public function renderInlines($inlines)
    {
        $result = [];
        foreach ($inlines as $inline) {
            $result[] = $this->renderInline($inline);
        }

        return implode('', $result);
    }

    /**
     * @param BlockElement $block
     * @param bool          $inTightList
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    public function renderBlock(BlockElement $block, $inTightList = false)
    {
        $renderer = $this->environment->getBlockRendererForClass(get_class($block));
        if (!$renderer) {
            throw new \RuntimeException('Unable to find corresponding renderer for block type ' . get_class($block));
        }

        return $renderer->render($block, $this, $inTightList);
    }

    /**
     * @param BlockElement[] $blocks
     * @param bool            $inTightList
     *
     * @return string
     */
    public function renderBlocks($blocks, $inTightList = false)
    {
        $result = [];
        foreach ($blocks as $block) {
            $result[] = $this->renderBlock($block, $inTightList);
        }

        $separator = $this->getOption('block_separator', "\n");

        return implode($separator, $result);
    }
}
