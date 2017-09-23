<?php

namespace twofox\languagepicker;

use Yii;
use yii\helpers\Url;

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
class Component extends \yii\base\Component{

    /**
     * @var function - function to execute after changing the language of the site.
     */
    public $callback;
    public $languages;
    public $cookieName = 'language';

    /**
     * @var integer expiration date of the cookie storing the language of the site.
     */
    public $expireDays = 30;

    /**
     * @var string The domain that the language cookie is available to.
     * For details see the $domain parameter description of PHP setcookie() function.
     */
    public $cookieDomain = '';

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
    public function init(){
        parent::init();
    }

    /**
     * Setting the language of the site.
     */
    public function initLanguage()
    {
        if ($language = Yii::$app->request->get('picker-language', false)) {
            if ($this->_isValidLanguage($language)) {
                $this->saveLanguage($language);
            }
        } else if (Yii::$app->request->cookies->has($this->cookieName)) {
            $language = Yii::$app->request->cookies->getValue($this->cookieName);
            if ($this->_isValidLanguage($language)) {
                if($language != Yii::$app->language)
                    return $this->_redirect($language);
            } else {
                Yii::$app->response->cookies->remove($this->cookieName);
            }
        }
        
        //$this->detectLanguage();
    }

    /**
     * Saving language into cookie and database.
     * @param string $language - The language to save.
     * @return static
     */
    public function saveLanguage($language){
        Yii::$app->language = $language;
        if (is_callable($this->callback)) {
            call_user_func($this->callback);
        }

        $this->saveLanguageIntoCookie($language);
    }



    /**
     * Save language into cookie.
     * @param string $language
     */
    public function saveLanguageIntoCookie($language)
    {
        $cookie = new \yii\web\Cookie([
            'name' => $this->cookieName,
            'domain' => $this->cookieDomain,
            'value' => $language,
            'expire' => time() + 86400 * $this->expireDays
        ]);

        Yii::$app->response->cookies->add($cookie);
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
    private function _redirect($language){
        return Yii::$app->response->redirect(Url::current(['language'=>$language]));
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