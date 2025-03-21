<?php

use Itsbessner\ConditionalContainer\Elements\ConditionalContainer;


$GLOBALS['TL_DCA']['tl_content']['fields']['itsbessner_conditional_selection'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['itsbessner_conditional_selection'],
    "exclude" => true,
    'inputType' => 'checkbox',
    'options_callback' => [ConditionalContainer::class, 'getOptions'],
    'eval' => ['multiple' => true, 'tl_class' => 'clr'],
    'sql' => "blob NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['itsbessner_conditional_days_before'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['itsbessner_conditional_days_before'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'natural', 'tl_class' => 'w50'],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];


$GLOBALS['TL_DCA']['tl_content']['palettes']['itsbessner_conditional_container'] = '{type_legend},itsbessner_conditional_selection,itsbessner_conditional_days_before';
