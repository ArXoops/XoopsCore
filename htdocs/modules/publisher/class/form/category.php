<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 *  Publisher form class
 *
 * @copyright       The XUUPS Project http://sourceforge.net/projects/xuups/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         Publisher
 * @since           1.0
 * @author          trabis <lusopoemas@gmail.com>
 * @version         $Id$
 */

defined('XOOPS_ROOT_PATH') or die("XOOPS root path not defined");

include_once dirname(dirname(dirname(__FILE__))) . '/include/common.php';

class PublisherCategoryForm extends XoopsThemeForm
{
    /**
     * @var int
     */
    private $_subCatsCount = 4;

    /**
     * @param int $count
     */
    public function setSubCatsCount($count)
    {
        $this->_subCatsCount = (int)$count;
    }

    /**
     * @param PublisherCategory $obj
     */
    public function __construct(PublisherCategory $obj)
    {
        $xoops = Xoops::getInstance();
        $publisher = Publisher::getInstance();

        $member_handler = $xoops->getHandlerMember();
        $userGroups = $member_handler->getGroupList();

        parent::__construct(_AM_PUBLISHER_CATEGORY, "form", $xoops->getEnv('PHP_SELF'));
        $this->setExtra('enctype="multipart/form-data"');

        // Category
        $criteria = new Criteria(null);
        $criteria->setSort('weight');
        $criteria->setOrder('ASC');
        $categories = $publisher->getCategoryHandler()->getObjects($criteria);
        $mytree = new XoopsObjectTree($categories, "categoryid", "parentid");
        $cat_select = $mytree->makeSelBox('parentid', 'name', '--', $obj->getVar('parentid'), true);
        $this->addElement(new XoopsFormLabel(_AM_PUBLISHER_PARENT_CATEGORY_EXP, $cat_select));

        // Name
        $this->addElement(new XoopsFormText(_AM_PUBLISHER_CATEGORY, 'name', 50, 255, $obj->getVar('name', 'e')), true);

        // Description
        $this->addElement(new XoopsFormTextArea(_AM_PUBLISHER_COLDESCRIPT, 'description', $obj->getVar('description', 'e'), 7, 60));

        // EDITOR
        $groups = $xoops->isUser() ? $xoops->user->getGroups() : XOOPS_GROUP_ANONYMOUS;
        $gperm_handler = $publisher->getGrouppermHandler();
        $module_id = $publisher->getModule()->mid();
        $allowed_editors = PublisherUtils::getEditors($gperm_handler->getItemIds('editors', $groups, $module_id));
        $nohtml = false;
        if (count($allowed_editors) > 0) {
            $editor = @$_POST['editor'];
            if (!empty($editor)) {
                PublisherUtils::setCookieVar('publisher_editor', $editor);
            } else {
                $editor = PublisherUtils::getCookieVar('publisher_editor');
                if (empty($editor) && $xoops->isUser()) {
                    $editor = $xoops->user->getVar('publisher_editor'); // Need set through user profile
                }
            }
            $editor = (empty($editor) || !in_array($editor, $allowed_editors)) ? $publisher->getConfig('submit_editor') : $editor;
            $form_editor = new XoopsFormSelectEditor($this, 'editor', $editor, $nohtml, $allowed_editors);
            $this->addElement($form_editor);
        } else {
            $editor = $publisher->getConfig('submit_editor');
        }

        $editor_configs = array();
        $editor_configs['rows'] = $publisher->getConfig('submit_editor_rows') == '' ? 35 : $publisher->getConfig('submit_editor_rows');
        $editor_configs['cols'] = $publisher->getConfig('submit_editor_cols') == '' ? 60 : $publisher->getConfig('submit_editor_cols');
        $editor_configs['width'] = $publisher->getConfig('submit_editor_width') == '' ? "100%" : $publisher->getConfig('submit_editor_width');
        $editor_configs['height'] = $publisher->getConfig('submit_editor_height') == '' ? "400px" : $publisher->getConfig('submit_editor_height');

        $editor_configs['name'] = 'header';
        $editor_configs['value'] = $obj->getVar('header', 'e');

        $text_header = new XoopsFormEditor(_AM_PUBLISHER_CATEGORY_HEADER, $editor, $editor_configs, $nohtml, $onfailure = null);
        $text_header->setDescription(_AM_PUBLISHER_CATEGORY_HEADER_DSC);
        $this->addElement($text_header);

        // IMAGE
        $image_array = XoopsLists::getImgListAsArray(PublisherUtils::getImageDir('category'));
        $image_select = new XoopsFormSelect('', 'image', $obj->image());
        //$image_select -> addOption ('-1', '---------------');
        $image_select->addOptionArray($image_array);
        $image_select->setExtra("onchange='showImgSelected(\"image3\", \"image\", \"" . 'uploads/' . PUBLISHER_DIRNAME . '/images/category/' . "\", \"\", \"" . XOOPS_URL . "\")'");
        $image_tray = new XoopsFormElementTray(_AM_PUBLISHER_IMAGE, '&nbsp;');
        $image_tray->addElement($image_select);
        $image_tray->addElement(new XoopsFormLabel('', "<br /><br /><img src='" . PublisherUtils::getImageDir('category', false) . $obj->image() . "' name='image3' id='image3' alt='' />"));
        $image_tray->setDescription(_AM_PUBLISHER_IMAGE_DSC);
        $this->addElement($image_tray);

        // IMAGE UPLOAD
        $max_size = 5000000;
        $file_box = new XoopsFormFile(_AM_PUBLISHER_IMAGE_UPLOAD, "image_file", $max_size);
        $file_box->setExtra("size ='45'");
        $file_box->setDescription(_AM_PUBLISHER_IMAGE_UPLOAD_DSC);
        $this->addElement($file_box);

        // Short url
        $text_short_url = new XoopsFormText(_AM_PUBLISHER_CATEGORY_SHORT_URL, 'short_url', 50, 255, $obj->getVar('short_url', 'e'));
        $text_short_url->setDescription(_AM_PUBLISHER_CATEGORY_SHORT_URL_DSC);
        $this->addElement($text_short_url);

        // Meta Keywords
        $text_meta_keywords = new XoopsFormTextArea(_AM_PUBLISHER_CATEGORY_META_KEYWORDS, 'meta_keywords', $obj->getVar('meta_keywords', 'e'), 7, 60);
        $text_meta_keywords->setDescription(_AM_PUBLISHER_CATEGORY_META_KEYWORDS_DSC);
        $this->addElement($text_meta_keywords);

        // Meta Description
        $text_meta_description = new XoopsFormTextArea(_AM_PUBLISHER_CATEGORY_META_DESCRIPTION, 'meta_description', $obj->getVar('meta_description', 'e'), 7, 60);
        $text_meta_description->setDescription(_AM_PUBLISHER_CATEGORY_META_DESCRIPTION_DSC);
        $this->addElement($text_meta_description);

        // Weight
        $this->addElement(new XoopsFormText(_AM_PUBLISHER_COLPOSIT, 'weight', 4, 4, $obj->getVar('weight')));

        // Added by skalpa: custom template support
        //todo, check this
        $this->addElement(new XoopsFormText("Custom template", 'template', 50, 255, $obj->getVar('template', 'e')), false);

        // READ PERMISSIONS
        $groups_read_checkbox = new XoopsFormCheckBox(_AM_PUBLISHER_PERMISSIONS_CAT_READ, 'groups_read[]', $obj->getGroups_read());
        foreach ($userGroups as $group_id => $group_name) {
            $groups_read_checkbox->addOption($group_id, $group_name);
        }
        $this->addElement($groups_read_checkbox);

        // SUBMIT PERMISSIONS
        $groups_submit_checkbox = new XoopsFormCheckBox(_AM_PUBLISHER_PERMISSIONS_CAT_SUBMIT, 'groups_submit[]', $obj->getGroups_submit());
        $groups_submit_checkbox->setDescription(_AM_PUBLISHER_PERMISSIONS_CAT_SUBMIT_DSC);
        foreach ($userGroups as $group_id => $group_name) {
            $groups_submit_checkbox->addOption($group_id, $group_name);
        }
        $this->addElement($groups_submit_checkbox);

        // MODERATION PERMISSIONS
        $groups_moderation_checkbox = new XoopsFormCheckBox(_AM_PUBLISHER_PERMISSIONS_CAT_MODERATOR, 'groups_moderation[]', $obj->getGroups_moderation());
        $groups_moderation_checkbox->setDescription(_AM_PUBLISHER_PERMISSIONS_CAT_MODERATOR_DSC);
        foreach ($userGroups as $group_id => $group_name) {
            $groups_moderation_checkbox->addOption($group_id, $group_name);
        }
        $this->addElement($groups_moderation_checkbox);

        $moderator = new XoopsFormSelectUser(_AM_PUBLISHER_CATEGORY_MODERATOR, 'moderator', true, $obj->getVar('moderator', 'e'), 1, false);
        $moderator->setDescription(_AM_PUBLISHER_CATEGORY_MODERATOR_DSC);
        $this->addElement($moderator);

        $cat_tray = new XoopsFormElementTray(_AM_PUBLISHER_SCATEGORYNAME, '<br /><br />');
        for ($i = 0; $i < $this->_subCatsCount; $i++) {
            if ($i < (isset($_POST['scname']) ? sizeof($_POST['scname']) : 0)) {
                $subname = isset($_POST['scname']) ? $_POST['scname'][$i] : '';
            } else {
                $subname = '';
            }
            $cat_tray->addElement(new XoopsFormText('', 'scname[' . $i . ']', 50, 255, $subname));
        }
        $t = new XoopsFormText('', 'nb_subcats', 3, 2);
        $l = new XoopsFormLabel('', sprintf(_AM_PUBLISHER_ADD_OPT, $t->render()));
        $b = new XoopsFormButton('', 'submit_subcats', _AM_PUBLISHER_ADD_OPT_SUBMIT, 'submit');

        if (!$obj->getVar('categoryid')) {
            $b->setExtra('onclick="this.form.elements.op.value=\'addsubcats\'"');
        } else {
            $b->setExtra('onclick="this.form.elements.op.value=\'mod\'"');
        }

        $r = new XoopsFormElementTray('');
        $r->addElement($l);
        $r->addElement($b);
        $cat_tray->addElement($r);
        $this->addElement($cat_tray);

        $this->addElement(new XoopsFormHidden('categoryid', $obj->getVar('categoryid')));
        $this->addElement(new XoopsFormHidden('nb_sub_yet', $this->_subCatsCount));

        // Action buttons tray
        $button_tray = new XoopsFormElementTray('', '');

        // No ID for category -- then it's new category, button says 'Create'
        if (!$obj->getVar('categoryid')) {

            $button_tray->addElement(new XoopsFormButton('', 'addcategory', _AM_PUBLISHER_CREATE, 'submit'));

            $butt_clear = new XoopsFormButton('', '', _AM_PUBLISHER_CLEAR, 'reset');
            $button_tray->addElement($butt_clear);

            $butt_cancel = new XoopsFormButton('', '', _AM_PUBLISHER_CANCEL, 'button');
            $butt_cancel->setExtra('onclick="history.go(-1)"');
            $button_tray->addElement($butt_cancel);

            $this->addElement($button_tray);
        } else {

            $button_tray->addElement(new XoopsFormButton('', 'addcategory', _AM_PUBLISHER_MODIFY, 'submit'));

            $butt_cancel = new XoopsFormButton('', '', _AM_PUBLISHER_CANCEL, 'button');
            $butt_cancel->setExtra('onclick="history.go(-1)"');
            $button_tray->addElement($butt_cancel);

            $this->addElement($button_tray);
        }
    }
}