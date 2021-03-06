/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./../../bootstrap');

import Vue from 'vue';

import DashboardPlugin from './../../plugins/dashboard-plugin';

import Global from './../../mixins/global';

import Form from './../../plugins/form';
import Error from './../../plugins/error';
import BulkAction from './../../plugins/bulk-action';

import { Link } from 'element-ui';

// plugin setup
Vue.use(DashboardPlugin, Link);

const app = new Vue({
    el: '#app',

    mixins: [
        Global
    ],

    data: function () {
        return {
            form: new Form('bill'),
            bulk_action: new BulkAction('bills'),
            totals: {
                sub: 0,
                discount: '',
                discount_text: false,
                tax: 0,
                total: 0
            },
            transaction_form: new Form('transaction'),
            payment: {
                modal: false,
                amount: 0,
                title: '',
                message: '',
                html: '',
                errors: new Error()
            },
            transaction: [],
            items: '',
            discount: false,
            currency: null,
        }
    },

    mounted() {
        this.form.items = [];

        if (this.form.method) {
            this.onAddItem();
        }

        if (typeof bill_items !== 'undefined' && bill_items) {
            let items = [];
            let currency_code = this.form.currency_code;

            bill_items.forEach(function(item) {
                items.push({
                    show: false,
                    currency: currency_code,
                    item_id: item.item_id,
                    name: item.name,
                    price: (item.price).toFixed(2),
                    quantity: item.quantity,
                    tax_id: item.tax_id,
                    total: (item.total).toFixed(2)
                });
            });

            this.form.items = items;
        }
    },

    methods:{
        onChangeContact(contact_id) {
            axios.get(url + '/purchases/vendors/' + contact_id + '/currency')
            .then(response => {
                this.form.contact_name = response.data.name;
                this.form.contact_email = response.data.email;
                this.form.contact_tax_number = response.data.tax_number;
                this.form.contact_phone = response.data.phone;
                this.form.contact_address = response.data.address;
                this.form.currency_code = response.data.currency_code;
                this.form.currency_rate = response.data.currency_rate;
            })
            .catch(error => {
            });
        },

        onChangeCurrency(currency_code) {
            axios.get(url + '/settings/currencies/currency', {
                params: {
                  code: currency_code
                }
            })
            .then(response => {
                this.currency = response.data;
                this.form.currency_code = response.data.currency_code;
                this.form.currency_rate = response.data.currency_rate;
            })
            .catch(error => {
            });
        },

        onCalculateTotal() {
            let sub_total = 0;
            let discount_total = 0;
            let tax_total = 0;
            let grand_total = 0;
            let items = this.form.items;
            let discount = this.form.discount;

            if (items.length) {
                let index = 0;

                for (index = 0; index < items.length; index++) {
                    let item = items[index];

                    let item_sub_total = item.price * item.quantity;
                    let item_tax_total = 0;
                    let item_discount_total = (discount) ? item_sub_total - (item_sub_total * (discount / 100)) : 0;

                    items[index].total = item_sub_total;

                    sub_total += items[index].total;
                    discount_total += item_discount_total;
                    tax_total += item_tax_total;
                    grand_total += sub_total + tax_total;
                }
            }

            this.totals.sub = sub_total;
            this.totals.discount = discount_total;
            this.totals.tax = tax_total;
            this.totals.total = grand_total;
            /*
            axios.post(url + '/common/items/total', {
                items: this.form.items,
                discount: this.form.discount,
                currency_code: this.form.currency_code
            })
            .then(response => {
                let items = this.form.items;

                response.data.items.forEach(function(value, index) {
                    items[index].total = value;
                });

                this.form.items = items;

                this.totals.sub = response.data.sub_total;
                this.totals.discount = response.data.discount_total;
                this.totals.tax = response.data.tax_total;
                this.totals.total = response.data.grand_total;
                this.totals.discount_text = response.data.discount_text;
            })
            .catch(error => {
            });*/
        },

        // add bill item row
        onAddItem() {
            let row = [];

            let keys = Object.keys(this.form.item_backup[0]);
            let currency_code = this.form.currency_code;

            keys.forEach(function(item) {
                if (item == 'currency') {
                    row[item] = currency_code;
                } else {
                    row[item] = '';
                }
            });

            this.form.items.push(Object.assign({}, row));
        },

        onGetItem(event, index) {
            let name = event.target.value;
            this.form.items[index].show = false;

            axios.get(url + '/common/items/autocomplete', {
                params: {
                    query: name,
                    type: 'bill',
                    currency_code: this.form.currency_code
                }
            })
            .then(response => {
                this.items = response.data;

                if (this.items.length) {
                    this.form.items[index].show = true;
                }
            })
            .catch(error => {
            });
        },

        onSelectItem(item, index) {
            this.form.items[index].item_id = item.id;
            this.form.items[index].name = item.name;
            this.form.items[index].price = (item.purchase_price).toFixed(2);
            this.form.items[index].quantity = 1;
            this.form.items[index].tax_id = [item.tax_id.toString()];
            this.form.items[index].total = (item.purchase_price).toFixed(2);
        },

        // remove bill item row => row_id = index
        onDeleteItem(index) {
            this.form.items.splice(index, 1);
        },

        onAddDiscount() {
            let discount = document.getElementById('pre-discount').value;

            this.form.discount = discount;
            this.discount = false;
        },

        onPayment() {
            this.payment.modal = true;

            let form = this.transaction_form;

            this.transaction_form = new Form('transaction');

            this.transaction_form.paid_at = form.paid_at;
            this.transaction_form.account_id = form.account_id;
            this.transaction_form.payment_method = form.payment_method;
        },

        addPayment() {
            this.transaction_form.submit();

            this.payment.errors = this.transaction_form.errors;

            this.form.loading = true;

            this.$emit("confirm");
        },

        closePayment() {
            this.payment = {
                modal: false,
                amount: 0,
                title: '',
                message: '',
                errors: this.transaction_form.errors
            };
        },

        // Change bank account get money and currency rate
        onChangePaymentAccount(account_id) {
            axios.get(url + '/banking/accounts/currency', {
                params: {
                    account_id: account_id
                }
            })
            .then(response => {
                this.transaction_form.currency = response.data.currency_name;
                this.transaction_form.currency_code = response.data.currency_code;
                this.transaction_form.currency_rate = response.data.currency_rate;

                this.money.decimal = response.data.decimal_mark;
                this.money.thousands = response.data.thousands_separator;
                this.money.prefix = (response.data.symbol_first) ? response.data.symbol : '';
                this.money.suffix = !(response.data.symbol_first) ? response.data.symbol : '';
                this.money.precision = response.data.precision;
            })
            .catch(error => {
            });
        },

        onDeleteTransaction(form_id) {
            this.transaction = new Form(form_id);

            this.confirm.url = this.transaction.action;
            this.confirm.title = this.transaction.title;
            this.confirm.message = this.transaction.message;
            this.confirm.button_cancel = this.transaction.cancel;
            this.confirm.button_delete = this.transaction.delete;
            this.confirm.show = true;
        }
    }
});
