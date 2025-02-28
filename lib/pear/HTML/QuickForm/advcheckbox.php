<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997, 1998, 1999, 2000, 2001 The PHP Group             |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Adam Daniel <adaniel1@eesus.jnj.com>                        |
// |          Bertrand Mansion <bmansion@mamasam.com>                     |
// +----------------------------------------------------------------------+
//
// $Id: advcheckbox.php,v 1.15 2005/06/24 17:58:29 avb Exp $

require_once('HTML/QuickForm/checkbox.php');

/**
 * HTML class for an advanced checkbox type field
 *
 * Basically this fixes a problem that HTML has had
 * where checkboxes can only pass a single value (the
 * value of the checkbox when checked).  A value for when
 * the checkbox is not checked cannot be passed, and 
 * furthermore the checkbox variable doesn't even exist if
 * the checkbox was submitted unchecked.
 *
 * It works by creating a hidden field with the passed-in name
 * and creating the checkbox with no name, but with a javascript
 * onclick which sets the value of the hidden field.
 * 
 * @author       Jason Rust <jrust@php.net>
 * @since        2.0
 * @access       public
 */
class HTML_QuickForm_advcheckbox extends HTML_QuickForm_checkbox
{
    // {{{ properties

    /**
     * The values passed by the hidden elment
     *
     * @var array
     * @access private
     */
    var $_values = null;

    /**
     * The default value
     *
     * @var boolean
     * @access private
     */
    var $_currentValue = null;

    // }}}
    // {{{ constructor

    /**
     * Class constructor
     * 
     * @param     string    $elementName    (optional)Input field name attribute
     * @param     string    $elementLabel   (optional)Input field label 
     * @param     string    $text           (optional)Text to put after the checkbox
     * @param     mixed     $attributes     (optional)Either a typical HTML attribute string 
     *                                      or an associative array
     * @param     mixed     $values         (optional)Values to pass if checked or not checked 
     *
     * @since     1.0
     * @access    public
     * @return    void
     */
    function HTML_QuickForm_advcheckbox($elementName=null, $elementLabel=null, $text=null, $attributes=null, $values=null)
    {
        $this->HTML_QuickForm_checkbox($elementName, $elementLabel, $text, $attributes);
        $this->setValues($values);
    } //end constructor
    
    // }}}
    // {{{ getPrivateName()

    /**
     * Gets the pribate name for the element
     *
     * @param   string  $elementName The element name to make private
     *
     * @access public
     * @return string
     */
    function getPrivateName($elementName)
    {
        return '__'.$elementName;
    }

    // }}}
    // {{{ getOnclickJs()

    /**
     * Create the javascript for the onclick event which will
     * set the value of the hidden field
     *
     * @param     string    $elementName    The element name
     *
     * @access public
     * @return string
     */
    function getOnclickJs($elementName)
    {
        $onclickJs = 'if (this.checked) { this.form[\''.$elementName.'\'].value=\''.addcslashes($this->_values[1], '\'').'\'; }';
        $onclickJs .= 'else { this.form[\''.$elementName.'\'].value=\''.addcslashes($this->_values[0], '\'').'\'; }';
        return $onclickJs;
    }

    // }}}
    // {{{ setValues()

    /**
     * Sets the values used by the hidden element
     *
     * @param   mixed   $values The values, either a string or an array
     *
     * @access public
     * @return void
     */
    function setValues($values)
    {
        if (empty($values)) {
            // give it default checkbox behavior
            $this->_values = array('', 1);
        } elseif (is_scalar($values)) {
            // if it's string, then assume the value to 
            // be passed is for when the element is checked
            $this->_values = array('', $values);
        } else {
            $this->_values = $values;
        }
        $this->setChecked($this->_currentValue == $this->_values[1]);
    }

    // }}}
    // {{{ setValue()

   /**
    * Sets the element's value
    * 
    * @param    mixed   Element's value
    * @access   public
    */
    function setValue($value)
    {
        $this->setChecked(isset($this->_values[1]) && $value == $this->_values[1]);
        $this->_currentValue = $value;
    }

    // }}}
    // {{{ getValue()

   /**
    * Returns the element's value
    *
    * @access   public
    * @return   mixed
    */
    function getValue()
    {
        if (is_array($this->_values)) {
            return $this->_values[$this->getChecked()? 1: 0];
        } else {
            return null;
        }
    }

    // }}}
    // {{{ toHtml()

    /**
     * Returns the checkbox element in HTML
     * and the additional hidden element in HTML
     * 
     * @access    public
     * @return    string
     */
    function toHtml()
    {
        if ($this->_flagFrozen) {
            return parent::toHtml();
        } else {
            $oldName = $this->getName();
            $oldJs   = $this->getAttribute('onclick');
            $this->updateAttributes(array(
                'name'    => $this->getPrivateName($oldName),
                'onclick' => $this->getOnclickJs($oldName) . ' ' . $oldJs
            ));
            $html = parent::toHtml() . '<input' .
                    $this->_getAttrString(array(
                        'type'  => 'hidden', 
                        'name'  => $oldName, 
                        'value' => $this->getValue()
                    )) . ' />';
            // revert the name and JS, in case this method will be called once more
            $this->updateAttributes(array(
                'name'    => $oldName, 
                'onclick' => $oldJs
            ));
            return $html;
        }
    } //end func toHtml
    
    // }}}
    // {{{ getFrozenHtml()

   /**
    * Unlike checkbox, this has to append a hidden input in both
    * checked and non-checked states
    */
    function getFrozenHtml()
    {
        return ($this->getChecked()? '<tt>[x]</tt>': '<tt>[ ]</tt>') .
               $this->_getPersistantData();
    }

    // }}}
    // {{{ onQuickFormEvent()

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param     string    $event  Name of event
     * @param     mixed     $arg    event arguments
     * @param     object    $caller calling object
     * @since     1.0
     * @access    public
     * @return    void
     */
    function onQuickFormEvent($event, $arg, &$caller)
    {
        switch ($event) {
            case 'updateValue':
                // constant values override both default and submitted ones
                // default values are overriden by submitted
                $value = $this->_findValue($caller->_constantValues);
                if (null === $value) {
                    $value = $this->_findValue($caller->_submitValues);
                    if (null === $value) {
                        $value = $this->_findValue($caller->_defaultValues);
                    }
                }
                if (null !== $value) {
                    $this->setValue($value);
                }
                break;
            default:
                parent::onQuickFormEvent($event, $arg, $caller);
        }
        return true;
    } // end func onQuickFormLoad

    // }}}
    // {{{ exportValue()

   /**
    * This element has a value even if it is not checked, thus we override
    * checkbox's behaviour here
    */
    function exportValue(&$submitValues, $assoc)
    {
        $value = $this->_findValue($submitValues);
        if (null === $value) {
            $value = $this->getValue();
        } elseif (is_array($this->_values) && ($value != $this->_values[0]) && ($value != $this->_values[1])) {
            $value = null;
        }
        return $this->_prepareValue($value, $assoc);
    }
    // }}}
} //end class HTML_QuickForm_advcheckbox
?>
