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


class Mirasvit_Email_Model_Observer extends Varien_Object
{
    public function sendQueue($observer)
    {
        $queueCollection = Mage::getModel('email/queue')->getCollection()
            ->addReadyFilter()
            ->setPageSize(10);

        foreach ($queueCollection as $item) {
            try {
                $item->send();
            } catch (Exception $e) {
                $item->error($e->__toString());
            }
        }
    }

    public function checkEvents()
    {
        $events = $this->getActiveEvents();

        foreach ($events as $eventCode) {
            $event = Mage::helper('email/event')->getEventModel($eventCode);
            if ($event) {
                $event->check($eventCode);
            }
        }

        $triggers = Mage::getModel('email/trigger')->getCollection()
            ->addActiveFilter();

        foreach ($triggers as $trigger) {
            $trigger->processNewEvents();
        }

        return true;
    }

    public function getActiveEvents()
    {
        $events = array();

        $triggers = Mage::getModel('email/trigger')->getCollection()
            ->addActiveFilter();

        foreach ($triggers as $trigger) {
            $events = array_merge($events, $trigger->getEvents());
        }

        $events = array_values(array_unique($events));

        return $events;
    }

    public function clearOldData()
    {
        $monthAgo = date('Y-m-d H:i:s', Mage::getSingleton('core/date')->gmtTimestamp() - 30 * 24 * 60 * 60);

        # Step 1. Remove old events
        $collection = Mage::getModel('email/event')->getCollection()
            ->addFieldToFilter('updated_at', array('lt' => $monthAgo));

        foreach ($collection as $event) {
            $event->delete();
        }

        # Step 2. Remove old mails
        $collection = Mage::getModel('email/queue')->getCollection()
            ->addFieldToFilter('status', array('neq' => Mirasvit_Email_Model_Queue::STATUS_PENDING))
            ->addFieldToFilter('scheduled_at', array('lt' => $monthAgo));

        foreach ($collection as $queue) {
            $queue->delete();
        }
    }

    public function onWishlistProductAdd($observer)
    {
        Mage::getModel('email/event_wishlist_wishlist')->observer('wishlist_wishlist_productadded', $observer);
    }

    public function onWishlistShared($observer)
    {
        Mage::getModel('email/event_wishlist_wishlist')->observer('wishlist_wishlist_shared', $observer);
    }

    public function onNewsletterSubscriberSaveAfter($observer)
    {
        $status = $observer->getEvent()->getDataObject()->getSubscriberStatus();

        if ($status == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
            Mage::getModel('email/event_customer_newsletter')->observer('customer_newsletter|subscribed', $observer);
        } elseif ($status == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
            Mage::getModel('email/event_customer_newsletter')->observer('customer_newsletter|unsubscribed', $observer);
        }
    }

    public function onEmailQueueGetContentAfter($observer)
    {
        $queue = $observer->getQueue();

        Mage::helper('email')->prepareQueueContent($queue);
    }
}