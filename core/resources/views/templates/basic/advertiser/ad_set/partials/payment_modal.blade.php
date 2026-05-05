
<div class="modal fade custom--modal modal-two" id="exampleModalTwo">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header__left">
                    <h6 class="modal-title"> Payment </h6>
                </div>
                <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="Close">
                    <span class="btn-close__icon"> <i class="las la-times"></i> </span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row gy-4">
                    <div class="col-sm-12">
                        <label class="form--label"> Select Your Payment Gateway </label>
                        <select class="form-select form--control img-select2 select2">
                            <option data-src="https://flagcdn.com/w40/us.png" value='en'>Paypal</option>
                            <option data-src="https://flagcdn.com/w40/es.png" value='es'>Paypal</option>
                        </select>
                    </div>
                    <div class="col-sm-12">
                        <label class="form--label"> Select Your Amount </label>
                        <input class="form--control" type="number" placeholder="1,000.00">
                    </div>
                    <div class="col-sm-12">
                        <ul class="amount-list">
                            <li class="amount-list__item">
                                <span> Limit </span>
                                <span> $1.00 - $10,000.00</span>
                            </li>
                            <li class="amount-list__item">
                                <span> Processing Charge </span>
                                <span> 1.90 USD </span>
                            </li>
                            <li class="amount-list__item total">
                                <span> Total </span>
                                <span> 91.90 USD </span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-sm-12">
                        <div class="btn--group flex-align justify-content-end gap-2">
                            <button class="btn btn--white btn--sm"> Cancel </button>
                            <button class="btn btn--base btn--sm"> Confirm </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
