const { Fragment: e, computed: t, createBlock: n, createCommentVNode: r, createElementBlock: i, createElementVNode: a, createTextVNode: o, createVNode: s, defineComponent: c, openBlock: l, ref: u, renderList: d, resolveComponent: f, toDisplayString: p, watch: m, withCtx: h, withKeys: g, withModifiers: _ } = window.__invoiceshelf_vue;
//#region resources/js/components/OfficeInvoiceItemsTable.vue?vue&type=script&setup=true&lang.ts
var v = {
	key: 0,
	class: "space-y-3"
}, y = { key: 1 }, b = { class: "flex items-center justify-between mb-4" }, x = { class: "flex items-center gap-2" }, S = { class: "text-sm font-semibold text-muted" }, C = {
	key: 0,
	class: "inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-status-green/10 text-status-green"
}, w = ["onClick"], T = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, E = { class: "relative" }, D = {
	key: 0,
	class: "mt-1 flex items-center gap-1.5 text-xs"
}, O = {
	key: 0,
	class: "text-status-green flex items-center gap-1"
}, k = {
	key: 1,
	class: "text-muted flex items-center gap-1"
}, A = {
	key: 1,
	class: "absolute z-50 left-0 right-0 mt-1 bg-surface border border-line-default rounded-lg shadow-xl max-h-56 overflow-y-auto"
}, j = ["onMousedown"], M = { class: "flex items-center justify-between font-semibold text-heading" }, N = { class: "text-xs font-normal text-muted" }, P = { class: "text-xs text-muted flex items-center gap-2 mt-0.5" }, F = { key: 0 }, I = { key: 1 }, L = /* @__PURE__ */ c({
	__name: "OfficeInvoiceItemsTable",
	props: {
		isLoading: {
			type: Boolean,
			default: !1
		},
		itemValidationScope: { default: "newInvoice" },
		store: {},
		storeProp: { default: "newInvoice" },
		isEdit: { type: Boolean }
	},
	setup(c) {
		let L = c, R = t(() => !L.store || !L.storeProp ? [] : L.store[L.storeProp]?.items || []), z = u({}), B = u({}), V = u({}), H = {};
		function U() {
			return window.__invoiceReceiptClient || window.__lrReceiptClient || window.axios || window.axios;
		}
		let W = () => {
			let e = {
				id: Math.random().toString(36).substr(2, 9),
				name: "",
				quantity: 1,
				price: 0,
				total: 0,
				discount: 0,
				discount_type: "fixed",
				discount_val: 0,
				taxes: [],
				tr_consignment_number: "",
				tr_consignment_date: "",
				tr_party_inv_no: "",
				tr_from_name: "",
				tr_to_name: "",
				tr_truck_no: "",
				tr_pkg_weight: "",
				tr_charged_weight: "",
				tr_rate: 0,
				tr_other_charge: 0,
				tr_lr_charge: 0,
				tr_dd_charge: 0,
				amount: 0
			};
			L.store[L.storeProp].items.push(e);
		}, G = (e, t) => {
			H[t] && (clearTimeout(H[t]), delete H[t]), delete z.value[t], delete B.value[t], delete V.value[t], R.value.splice(t, 1);
		}, K = (e) => {
			let t = Number(e);
			return isNaN(t) ? 0 : t;
		}, q = (e) => {
			let t = K(e.tr_rate), n = K(e.tr_other_charge), r = K(e.tr_lr_charge), i = K(e.tr_dd_charge);
			return t + n + r + i;
		}, J = (e) => {
			let t = q(e);
			e.amount = t;
			let n = Math.round(t * 100);
			e.price = n, e.total = n, e.quantity = 1, e.name = e.tr_consignment_number || "Consignment";
		};
		function Y(e, t, n) {
			if (e.tr_consignment_number = t.consignment_number, t.consignment_date && (e.tr_consignment_date = t.consignment_date), t.party_inv_no && (e.tr_party_inv_no = t.party_inv_no), t.from_code && (e.tr_from_name = t.from_code), t.to_code && (e.tr_to_name = t.to_code), t.truck_no && (e.tr_truck_no = t.truck_no), t.pkg && (e.tr_pkg_weight = t.pkg), t.weight && (e.tr_charged_weight = t.weight), t.rate !== void 0 && t.rate !== null && (e.tr_rate = Number(t.rate)), t.other_charge !== void 0 && t.other_charge !== null && (e.tr_other_charge = Number(t.other_charge)), t.lr_charge !== void 0 && t.lr_charge !== null && (e.tr_lr_charge = Number(t.lr_charge)), t.dd_charge !== void 0 && t.dd_charge !== null && (e.tr_dd_charge = Number(t.dd_charge)), J(e), L.store?.newInvoice) {
				!(L.store.newInvoice.customer_id || L.store.newInvoice.customer?.id) && t.customer_id && (t.customer ? (L.store.newInvoice.customer = t.customer, L.store.newInvoice.customer_id = t.customer_id, t.customer.currency_id && (L.store.newInvoice.currency_id = t.customer.currency_id)) : L.store.newInvoice.customer_id = t.customer_id, typeof L.store.selectCustomer == "function" && L.store.selectCustomer(t.customer_id).catch(() => {})), !L.store.newInvoice.tr_consignee_customer_id && t.consignee_customer_id && (L.store.newInvoice.tr_consignee_customer_id = t.consignee_customer_id, t.consignee && (L.store.newInvoice.consignee = t.consignee));
				let e = t.gst_tax_payable_by || t.tr_gst_payable_by;
				if (e && (L.store.newInvoice.tr_gst_payable_by || (L.store.newInvoice.tr_gst_payable_by = e), L.store.newInvoice.gst_tax_payable_by || (L.store.newInvoice.gst_tax_payable_by = e), Array.isArray(L.store.newInvoice.customFields))) for (let t of L.store.newInvoice.customFields) {
					let n = (t.label || "").toLowerCase().trim();
					(n === "gst tax payable by" || n === "gst tax through" || n === "gst payable by") && (t.value = e);
				}
			}
			B.value[n] = [], V.value[n] = {
				type: "success",
				message: `Autofilled from LR Receipt #${t.consignment_number}`
			};
		}
		async function X(e, t, n) {
			let r = n.trim();
			if (!r) return;
			let i = U();
			if (i) {
				z.value[t] = !0;
				try {
					let n = await i.get(`/api/v1/invoice-receipts/consignments/${encodeURIComponent(r)}`);
					n.data?.data ? Y(e, n.data.data, t) : V.value[t] = {
						type: "not_found",
						message: "No LR Receipt found"
					};
				} catch {
					V.value[t] = {
						type: "not_found",
						message: "No LR Receipt found"
					};
				} finally {
					z.value[t] = !1;
				}
			}
		}
		function Z(e, t, n) {
			e.tr_consignment_number = n, H[t] && clearTimeout(H[t]);
			let r = (n || "").trim();
			if (!r) {
				B.value[t] = [], V.value[t] = null;
				return;
			}
			H[t] = setTimeout(async () => {
				let n = U();
				if (n) {
					z.value[t] = !0;
					try {
						let i = (await n.get("/api/v1/invoice-receipts/consignments/search", { params: { query: r } })).data?.data || [];
						B.value[t] = i;
						let a = i.find((e) => e.consignment_number.toLowerCase() === r.toLowerCase());
						a ? Y(e, a, t) : i.length === 0 && (V.value[t] = {
							type: "not_found",
							message: "No LR Receipt found"
						});
					} catch {} finally {
						z.value[t] = !1;
					}
				}
			}, 350);
		}
		function Q(e, t) {
			setTimeout(() => {
				B.value[t] = [], e.tr_consignment_number && !V.value[t] && X(e, t, e.tr_consignment_number);
			}, 200);
		}
		function $(e, t) {
			B.value[t]?.length ? Y(e, B.value[t][0], t) : e.tr_consignment_number && X(e, t, e.tr_consignment_number);
		}
		return m(R, (e) => {
			e.forEach((e) => J(e));
		}, {
			deep: !0,
			immediate: !0
		}), (t, u) => {
			let m = f("BaseContentPlaceholdersBox"), L = f("BaseContentPlaceholders"), H = f("BaseIcon"), U = f("BaseInput"), K = f("BaseInputGroup"), J = f("BaseCard");
			return l(), n(J, { class: "mb-6" }, {
				header: h(() => [...u[0] ||= [a("div", { class: "flex items-center justify-between" }, [a("h3", { class: "text-base font-semibold text-heading" }, "Consignment Items"), a("span", { class: "text-xs text-muted" }, " Type Consignment No to autofill details from LR Receipt ")], -1)]]),
				default: h(() => [c.isLoading ? (l(), i("div", v, [(l(), i(e, null, d(2, (e) => s(L, { key: e }, {
					default: h(() => [s(m, {
						rounded: !0,
						class: "w-full",
						style: { "min-height": "60px" }
					})]),
					_: 1
				})), 64))])) : (l(), i("div", y, [(l(!0), i(e, null, d(R.value, (t, n) => (l(), i("div", {
					key: t.id ?? n,
					class: "border border-line-light rounded-lg p-4 mb-4 relative"
				}, [a("div", b, [a("div", x, [a("span", S, "Item " + p(n + 1), 1), V.value[n]?.type === "success" ? (l(), i("span", C, [s(H, {
					name: "CheckCircleIcon",
					class: "h-3.5 w-3.5"
				}), u[1] ||= o(" LR Linked ", -1)])) : r("", !0)]), R.value.length > 1 ? (l(), i("button", {
					key: 0,
					type: "button",
					class: "text-alert-error-text hover:text-alert-error-text cursor-pointer",
					onClick: (e) => G(t.id, n)
				}, [s(H, {
					name: "TrashIcon",
					class: "h-5 w-5"
				})], 8, w)) : r("", !0)]), a("div", T, [
					a("div", E, [
						s(K, {
							label: "Consignment No",
							required: ""
						}, {
							default: h(() => [s(U, {
								"model-value": t.tr_consignment_number,
								type: "text",
								placeholder: "Enter consignment number",
								loading: z.value[n] || !1,
								"loading-position": "right",
								"onUpdate:modelValue": (e) => Z(t, n, e),
								onBlur: (e) => Q(t, n),
								onKeydown: g(_((e) => $(t, n), ["prevent"]), ["enter"])
							}, null, 8, [
								"model-value",
								"loading",
								"onUpdate:modelValue",
								"onBlur",
								"onKeydown"
							])]),
							_: 2
						}, 1024),
						V.value[n] ? (l(), i("div", D, [V.value[n]?.type === "success" ? (l(), i("span", O, [s(H, {
							name: "CheckCircleIcon",
							class: "h-3.5 w-3.5"
						}), o(" " + p(V.value[n]?.message), 1)])) : V.value[n]?.type === "not_found" ? (l(), i("span", k, [s(H, {
							name: "InformationCircleIcon",
							class: "h-3.5 w-3.5"
						}), o(" " + p(V.value[n]?.message), 1)])) : r("", !0)])) : r("", !0),
						B.value[n] && B.value[n].length > 0 ? (l(), i("div", A, [(l(!0), i(e, null, d(B.value[n], (e) => (l(), i("div", {
							key: e.id,
							class: "px-3 py-2 cursor-pointer hover:bg-hover border-b border-line-light last:border-b-0 text-sm transition-colors",
							onMousedown: _((r) => Y(t, e, n), ["prevent"])
						}, [a("div", M, [a("span", null, "LR #" + p(e.consignment_number), 1), a("span", N, p(e.consignment_date || ""), 1)]), a("div", P, [
							a("span", null, p(e.from_code || "-") + " → " + p(e.to_code || "-"), 1),
							e.truck_no ? (l(), i("span", F, "• " + p(e.truck_no), 1)) : r("", !0),
							e.rate ? (l(), i("span", I, "• Rate: ₹" + p(e.rate), 1)) : r("", !0)
						])], 40, j))), 128))])) : r("", !0)
					]),
					s(K, { label: "Consignment Date" }, {
						default: h(() => [s(U, {
							modelValue: t.tr_consignment_date,
							"onUpdate:modelValue": (e) => t.tr_consignment_date = e,
							type: "date"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(K, { label: "Party Inv No" }, {
						default: h(() => [s(U, {
							modelValue: t.tr_party_inv_no,
							"onUpdate:modelValue": (e) => t.tr_party_inv_no = e,
							type: "text",
							placeholder: "Party invoice number"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(K, { label: "From" }, {
						default: h(() => [s(U, {
							modelValue: t.tr_from_name,
							"onUpdate:modelValue": (e) => t.tr_from_name = e,
							type: "text",
							placeholder: "Origin"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(K, { label: "Destination" }, {
						default: h(() => [s(U, {
							modelValue: t.tr_to_name,
							"onUpdate:modelValue": (e) => t.tr_to_name = e,
							type: "text",
							placeholder: "Destination"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(K, { label: "Vehicle No" }, {
						default: h(() => [s(U, {
							modelValue: t.tr_truck_no,
							"onUpdate:modelValue": (e) => t.tr_truck_no = e,
							type: "text",
							placeholder: "Truck/vehicle number"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(K, { label: "Pkg" }, {
						default: h(() => [s(U, {
							modelValue: t.tr_pkg_weight,
							"onUpdate:modelValue": (e) => t.tr_pkg_weight = e,
							type: "text",
							placeholder: "Package type"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(K, { label: "Weight" }, {
						default: h(() => [s(U, {
							modelValue: t.tr_charged_weight,
							"onUpdate:modelValue": (e) => t.tr_charged_weight = e,
							type: "text",
							placeholder: "Shipment weight"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(K, { label: "Rate" }, {
						default: h(() => [s(U, {
							modelValue: t.tr_rate,
							"onUpdate:modelValue": (e) => t.tr_rate = e,
							modelModifiers: { number: !0 },
							type: "number",
							placeholder: "Freight rate"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(K, { label: "Other Charge" }, {
						default: h(() => [s(U, {
							modelValue: t.tr_other_charge,
							"onUpdate:modelValue": (e) => t.tr_other_charge = e,
							modelModifiers: { number: !0 },
							type: "number",
							placeholder: "0.00"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(K, { label: "LR Charge" }, {
						default: h(() => [s(U, {
							modelValue: t.tr_lr_charge,
							"onUpdate:modelValue": (e) => t.tr_lr_charge = e,
							modelModifiers: { number: !0 },
							type: "number",
							placeholder: "0.00"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(K, { label: "DD Charge" }, {
						default: h(() => [s(U, {
							modelValue: t.tr_dd_charge,
							"onUpdate:modelValue": (e) => t.tr_dd_charge = e,
							modelModifiers: { number: !0 },
							type: "number",
							placeholder: "0.00"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(K, {
						label: "Amount",
						class: "md:col-span-2 xl:col-span-1"
					}, {
						default: h(() => [s(U, {
							"model-value": q(t),
							disabled: "",
							class: "bg-surface-muted font-semibold"
						}, null, 8, ["model-value"])]),
						_: 2
					}, 1024)
				])]))), 128)), a("button", {
					type: "button",
					class: "flex items-center justify-center w-full px-6 py-3 text-base border border-dashed border-line-default rounded-lg cursor-pointer text-primary-500 hover:bg-hover",
					onClick: W
				}, [s(H, {
					name: "PlusIcon",
					class: "h-5 w-5 mr-2"
				}), u[2] ||= o(" Add Item ", -1)])]))]),
				_: 1
			});
		};
	}
}), R = {
	key: 0,
	class: "space-y-6 mt-5"
}, z = /* @__PURE__ */ c({
	__name: "OfficeInvoiceFormFields",
	props: {
		templateName: {},
		store: {}
	},
	setup(e) {
		let n = e, a = t(() => n.store?.newInvoice), o = t(() => n.templateName === "invoice_receipt");
		return (t, n) => o.value && a.value ? (l(), i("div", R, [s(L, {
			"is-loading": !1,
			store: e.store,
			"store-prop": "newInvoice"
		}, null, 8, ["store"])])) : r("", !0);
	}
});
//#endregion
//#region resources/js/init.ts
window.InvoiceShelf.booting((e, t, n) => {
	window.__invoiceReceiptClient = n.client, n.addMessages({ en: { invoice_receipt: {
		title: "Invoice Receipts",
		gst_tax_through: "GST Tax Through",
		invoice_receipt_settings: "Invoice Receipt Settings"
	} } }), n.registerInvoiceFormSection({
		id: "office-invoice-fields",
		component: z
	}), n.registerInvoiceViewMode({
		id: "invoice-receipt-view-mode",
		value: "invoice_receipt",
		label: "Invoice Receipt",
		icon: "DocumentTextIcon",
		createLink: "invoices/create?template=invoice_receipt",
		listLink: "/admin/invoices?view=invoice_receipt",
		ability: "invoice-receipt:view-invoice-receipt"
	}), n.registerInvoiceDocumentMeta({
		id: "invoice-receipt-doc-meta",
		templateName: "invoice_receipt",
		label: "Invoice Receipt",
		labelPlural: "Invoice Receipts",
		listLink: "/admin/invoices?view=invoice_receipt",
		dateLabel: "Receipt Date",
		numberLabel: "Receipt No."
	});
});
//#endregion
