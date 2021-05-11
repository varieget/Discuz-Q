<?php

namespace App\Common;

class PermissionKey
{
    const CREATE_THREAD = 'createThread';

    const THREAD_INSERT_IMAGE = 'thread.insertImage';
    const THREAD_INSERT_VIDEO = 'thread.insertVideo';
    const THREAD_INSERT_AUDIO = 'thread.insertAudio';
    const THREAD_INSERT_ATTACHMENT = 'thread.insertAttachment';
    const THREAD_INSERT_GOODS = 'thread.insertGoods';
    const THREAD_INSERT_PAY = 'thread.insertPay';
    const THREAD_INSERT_REWARD = 'thread.insertReward';
    const THREAD_INSERT_RED_PACKET = 'thread.insertRedPacket';
    const THREAD_INSERT_POSITION = 'thread.insertPosition';

    const THREAD_ALLOW_ANONYMOUS = 'thread.allowAnonymous';

    const VIEW_THREADS = 'viewThreads';
    const THREAD_REPLY = 'thread.reply';
    const THREAD_VIEW_DETAIL = 'thread.viewPosts';
    const THREAD_FREE_VIEW = 'thread.freeViewPosts';
    const THREAD_DELETE = 'thread.hide';
    const THREAD_EDIT = 'thread.edit';
    const REPLY_DELETE = 'thread.hidePosts';
    const OWN_THREAD_EDIT = 'thread.editOwnThreadOrPost';
    const OWN_THREAD_DELETE = 'thread.hideOwnThreadOrPost';
    const THREAD_ESSENCE = 'thread.essence';

    const DIALOG_CREATE = 'dialog.create';
    const THREAD_STICKY = 'thread.sticky';
    const CREATE_INVITE = 'createInvite';

    const CASH_CREATE = 'cash.create';
    const ORDER_CREATE = 'order.create';

}
