<?php

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Extension\Event;

/**
 * DokuWiki Plugin emailpolicy (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author Phil Underwood <beardydoc@gmail.com>
 */
class action_plugin_emailpolicy extends ActionPlugin
{
    /** @inheritDoc */
    public function register(EventHandler $controller)
    {
        $controller->register_hook('AUTH_USER_CHANGE', 'BEFORE', $this, 'handleAuthUserChange');

    }


    /**
     * Event handler for AUTH_USER_CHANGE
     *
     * @see https://www.dokuwiki.org/devel:events:AUTH_USER_CHANGE
     * @param Event $event Event object
     * @param mixed $param optional parameter passed when event was registered
     * @return void
     */
     
    private function endsWith($string, $endString) 
    { 
        $len = strlen($endString); 
        if ($len == 0) { 
            return true; 
        } 
        return (substr($string, -$len) === $endString); 
    }  
     
    public function handleAuthUserChange(Event $event, $param)
    {
        if ($event->data['type'] == 'create') {
            $email = $event->data['params'][3];
        } elseif ($event->data['type'] == 'modify') {
            // may want to get username and check groups separately
            if (!isset($event->data['params'][1]['mail'])) {
                return; //email is not changed, nothing to do
            }
            $email = $event->data['params'][1]['mail'];
        } else {
            return;
        }
        $allow = $this->getConf('allow');
        $deny = $this->getConf('deny');
        dbglog('allow: '.implode(',',$allow));
        dbglog('deny: '.implode(',',$deny));
        if (count($allow) > 0)  {
            $pass = false;
            foreach ($allow as $x) {
                if ($this->endsWith($email, $x)) {
                     print("matched with".$x);
                     $pass = true;
                }
            }
            if (!$pass) {
                msg($this->getLang('allow_failed') . implode(', ',$allow), -1);
                $event->preventDefault();
                $event->stopPropagation();
            }
        }
        if (count($deny) > 0) {
            foreach ($deny as $x) {
                if ($this->endsWith($email, $x)) {
                    msg($this->getLang('deny_failed') . implode(', ',$deny), -1);
                    $event->preventDefault();
                    $event->stopPropagation();
                }
            }
        }
    }
}
