<?php

namespace twofox\languagepicker\bundles;

use yii\web\AssetBundle;

/**
 * LanguagePlugin asset bundle
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
class LanguagePluginAsset extends AssetBundle {

    /**
     * @inheritdoc
     */
    public $sourcePath = '@vendor/2fox/yii2-language-picker/assets';

    /**
     * @inheritdoc
     */
    public $js = [
        'javascripts/language-picker.min.js',
    ];

    /**
     * @inheritdoc
     */
    public $depends = [
        'yii\web\JqueryAsset',
    ];

}
