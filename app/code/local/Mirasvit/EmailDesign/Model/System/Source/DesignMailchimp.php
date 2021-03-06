<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Follow Up Email
 * @version   1.0.2
 * @revision  269
 * @copyright Copyright (C) 2014 Mirasvit (http://mirasvit.com/)
 */


class Mirasvit_EmailDesign_Model_System_Source_DesignMailchimp
{
    public function toOptionArray($filesystem = false)
    {
        $result = array();


        $path = Mage::getSingleton('emaildesign/config')->getMailchimpPath();
        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if (substr($entry, 0, 1) != '.') {
                    $result[] = array(
                        'label' => $entry,
                        'value' => $path.DS.$entry
                    );
                }
            }
            closedir($handle);
        }

        return $result;
    }
}