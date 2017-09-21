<?php

namespace twofox\languagepicker;

use Yii;

/**
 * Component.
 *
 * Examples:
 *
 * Minimal code:
 *
 * ~~~
 * 'language' => 'en',
 * 'bootstrap' => ['languagepicker'],
 * 'components' => [
 *      'languagepicker' => [
 *          'class' => 'lajax\languagepicker\Component',
 *          'languages' => ['en', 'de', 'fr']               // List of available languages
 *      ]
 * ],
 * ~~~
 *
 * Complete example:
 *
 * ~~~
 * 'language' => 'en-US',
 * 'bootstrap' => ['languagepicker'],
 * 'components' => [
 *      'languagepicker' => [
 *          'class' => 'lajax\languagepicker\Component',
 *          'languages' => ['en-US', 'de-DE', 'fr-FR'],     // List of available languages
 *          'callback' => function() {
 *              if (!\Yii::$app->user->isGuest) {
 *                  $user = User::findOne(\Yii::$app->user->id);
 *                  $user->language = \Yii::$app->language;
 *                  $user->save();
 *              }
 *          }
 *      ]
 * ]
 * ~~~
 *
 *
 * @author Lajos Molnar <lajax.m@gmail.com>
 * @since 1.0
 */
class Component extends \lajax\languagepicker\Component{

    /**
     * @var function - function to execute after changing the language of the site.
     */
    public $callback;
    public $languages;

    /**
     * @inheritdoc
     * @param array $config
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct($config = array())
    {

        if (empty($config['languages'])) {
            throw new \yii\base\InvalidConfigException('Missing languages');
        } else if (is_callable($config['languages'])) {
            $config['languages'] = call_user_func($config['languages']);
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {

        $this->initLanguage();

        parent::init();
    }

    /**
     * Setting the language of the site.
     */
    public function initLanguage()
    {
        if (isset($_GET['picker-language'])) {
            if ($this->_isValidLanguage($_GET['picker-language'])) {
                return $this->saveLanguage($_GET['picker-language']);
            } else if (!Yii::$app->request->isAjax) {
                return $this->_redirect();
            }
        } else if (Yii::$app->request->cookies->has($this->cookieName)) {
            if ($this->_isValidLanguage(Yii::$app->request->cookies->getValue($this->cookieName))) {
                Yii::$app->language = Yii::$app->request->cookies->getValue($this->cookieName);
                return;
            } else {
                Yii::$app->response->cookies->remove($this->cookieName);
            }
        }
        
        $this->detectLanguage();
    }

    /**
     * Saving language into cookie and database.
     * @param string $language - The language to save.
     * @return static
     */
    public function saveLanguage($language)
    {

        Yii::$app->language = $language;

        if (is_callable($this->callback)) {
            call_user_func($this->callback);
        }

        if (Yii::$app->request->isAjax) {
            Yii::$app->end();
        }

        return $this->_redirect();
    }

    /**
     * Determine language based on UserAgent.
     */
    public function detectLanguage()
    {
        $acceptableLanguages = Yii::$app->getRequest()->getAcceptableLanguages();
        foreach ($acceptableLanguages as $language) {
            if ($this->_isValidLanguage($language)) {
                Yii::$app->language = $language;
                return;
            }
        }

        foreach ($acceptableLanguages as $language) {
            $pattern = preg_quote(substr($language, 0, 2), '/');
            foreach ($this->languages as $key => $value) {
                if (preg_match('/^' . $pattern . '/', $value) || preg_match('/^' . $pattern . '/', $key)) {
                    Yii::$app->language = $this->_isValidLanguage($key) ? $key : $value;
                    return;
                }
            }
        }
    }

    /**
     * Redirects the browser to the referer URL.
     * @return static
     */
    private function _redirect()
    {
        $redirect = Yii::$app->request->absoluteUrl == Yii::$app->request->referrer ? '/' : Yii::$app->request->referrer;
        return Yii::$app->response->redirect($redirect);
    }

    /**
     * Determines whether the language received as a parameter can be processed.
     * @param string $language
     * @return boolean
     */
    private function _isValidLanguage($language)
    {
        return is_string($language) && (isset($this->languages[$language]) || in_array($language, $this->languages));
    }

}