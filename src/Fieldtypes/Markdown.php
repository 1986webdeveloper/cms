<?php

namespace Statamic\Fieldtypes;

use Statamic\Facades\GraphQL;
use Statamic\Fields\Fieldtype;
use Statamic\Query\Scopes\Filters\Fields\Markdown as MarkdownFilter;
use Statamic\Support\Html;

class Markdown extends Fieldtype
{
    protected $categories = ['text'];

    use Concerns\ResolvesStatamicUrls;

    protected function configFieldItems(): array
    {
        return [
            [
                'display' => __('Editor'),
                'fields' => [
                    'automatic_line_breaks' => [
                        'display' => __('Automatic Line Breaks'),
                        'instructions' => __('statamic::fieldtypes.markdown.config.automatic_line_breaks'),
                        'type' => 'toggle',
                        'default' => true,
                    ],
                    'automatic_links' => [
                        'display' => __('Automatic Links'),
                        'instructions' => __('statamic::fieldtypes.markdown.config.automatic_links'),
                        'type' => 'toggle',
                        'default' => false,
                    ],
                    'escape_markup' => [
                        'display' => __('Escape Markup'),
                        'instructions' => __('statamic::fieldtypes.markdown.config.escape_markup'),
                        'type' => 'toggle',
                        'default' => false,
                    ],
                    'smartypants' => [
                        'display' => __('Smartypants'),
                        'instructions' => __('statamic::fieldtypes.markdown.config.smartypants'),
                        'type' => 'toggle',
                        'default' => false,
                    ],
                    'parser' => [
                        'display' => __('Parser'),
                        'instructions' => __('statamic::fieldtypes.markdown.config.parser'),
                        'type' => 'text',
                    ],
                    'default' => [
                        'display' => __('Default Value'),
                        'instructions' => __('statamic::messages.fields_default_instructions'),
                        'type' => 'markdown',
                    ],
                ],
            ],
            [
                'display' => __('Assets'),
                'fields' => [
                    'container' => [
                        'display' => __('Container'),
                        'instructions' => __('statamic::fieldtypes.markdown.config.container'),
                        'type' => 'asset_container',
                        'mode' => 'select',
                        'max_items' => 1,
                    ],
                    'folder' => [
                        'display' => __('Folder'),
                        'instructions' => __('statamic::fieldtypes.markdown.config.folder'),
                        'type' => 'asset_folder',
                        'max_items' => 1,
                        'if' => [
                            'container' => 'not empty',
                        ],
                    ],
                    'restrict' => [
                        'display' => __('Restrict'),
                        'instructions' => __('statamic::fieldtypes.markdown.config.restrict'),
                        'type' => 'toggle',
                    ],
                ],
            ],
            [
                'display' => 'Antlers',
                'fields' => [
                    'antlers' => [
                        'display' => __('Allow Antlers'),
                        'instructions' => __('statamic::fieldtypes.any.config.antlers'),
                        'type' => 'toggle',
                    ],
                ],
            ],
        ];
    }

    public function filter()
    {
        return new MarkdownFilter($this);
    }

    public function augment($value)
    {
        if (is_null($value)) {
            return;
        }

        $markdown = \Statamic\Facades\Markdown::parser(
            $this->config('parser', 'default')
        );

        if ($this->config('automatic_line_breaks')) {
            $markdown = $markdown->withAutoLineBreaks();
        }

        if ($this->config('escape_markup')) {
            $markdown = $markdown->withMarkupEscaping();
        }

        if ($this->config('automatic_links')) {
            $markdown = $markdown->withAutoLinks();
        }

        if ($this->config('smartypants')) {
            $markdown = $markdown->withSmartPunctuation();
        }

        $value = $this->resolveStatamicUrls($value);

        $html = $markdown->parse((string) $value);

        return $html;
    }

    public function preProcessIndex($value)
    {
        return $value ? Html::markdown($value) : $value;
    }

    public function toGqlType()
    {
        return [
            'type' => GraphQL::string(),
            'args' => [
                'format' => [
                    'type' => GraphQL::string(),
                    'description' => 'How the value should be formatted. Either "markdown" or "html". Defaults to "html".',
                    'defaultValue' => 'html',
                ],
            ],
            'resolve' => function ($entry, $args, $context, $info) {
                return $args['format'] == 'html'
                    ? $entry->resolveGqlValue($info->fieldName)
                    : $entry->resolveRawGqlValue($info->fieldName);
            },
        ];
    }

    public function preload()
    {
        return [
            'previewUrl' => cp_route('markdown.preview'),
        ];
    }
}
