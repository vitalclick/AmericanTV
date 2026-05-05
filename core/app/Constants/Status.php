<?php

namespace App\Constants;

class Status {

    const ENABLE  = 1;
    const DISABLE = 0;

    const YES = 1;
    const NO  = 0;

    const VERIFIED   = 1;
    const UNVERIFIED = 0;

    const PAYMENT_INITIATE = 0;
    const PAYMENT_SUCCESS  = 1;
    const PAYMENT_PENDING  = 2;
    const PAYMENT_REJECT   = 3;

    CONST TICKET_OPEN   = 0;
    CONST TICKET_ANSWER = 1;
    CONST TICKET_REPLY  = 2;
    CONST TICKET_CLOSE  = 3;

    CONST PRIORITY_LOW    = 1;
    CONST PRIORITY_MEDIUM = 2;
    CONST PRIORITY_HIGH   = 3;

    const USER_ACTIVE = 1;
    const USER_BAN    = 0;

    const KYC_UNVERIFIED = 0;
    const KYC_PENDING    = 2;
    const KYC_VERIFIED   = 1;

    const GOOGLE_PAY = 5001;

    const CUR_BOTH = 1;
    const CUR_TEXT = 2;
    const CUR_SYM  = 3;

    const PUBLIC  = 0;
    const PRIVATE = 1;

    const PUBLISHED = 1;
    const REJECTED  = 2;
    const DRAFT     = 0;

    const FIRST_STEP  = 1;
    const SECOND_STEP = 2;
    const THIRD_STEP  = 3;
    const FOURTH_STEP = 4;

    const MONETIZATION_APPLYING = 2;
    const MONETIZATION_CANCEL   = 3;
    const MONETIZATION_APPROVED = 1;
    const MONETIZATION_INITIATE = 0;

    const ADVERTISER_APPROVED = 1;
    const ADVERTISER_PENDING  = 2;
    const ADVERTISER_REJECTED = 3;

    const IMPRESSION = 1;
    const CLICK      = 2;
    const BOTH       = 3;

    const RUNNING = 1;
    const PAUSE   = 2;
    const INITIATE   = 0;

    const ADVERTISEMENT_PENDING = 0;
    const ADVERTISEMENT_REJECTED = 3;

    const LOCAL_SERVER         = 1;
    const FTP_SERVER           = 2;
    const WASABI_SERVER        = 3;
    const DIGITAL_OCEAN_SERVER = 4;

    const ALL_VIEWS = 1;
    const SKIPPABLE = 2;
    const NON_SKIPPABLE = 3;
    const IN_FEED = 4;
 

    const ALL_DAYS = 1;
    const CUSTOM_DAYS = 2;


     const DEFAULT = 0;
    const ADVANCED = 1;
    
}
