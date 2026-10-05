const { Fragment: e, computed: t, createBlock: n, createCommentVNode: r, createElementBlock: i, createElementVNode: a, createTextVNode: o, createVNode: s, defineComponent: c, openBlock: l, renderList: u, resolveComponent: d, toDisplayString: f, watch: p, withCtx: m } = window.__invoiceshelf_vue;
//#region resources/js/components/OfficeInvoiceItemsTable.vue?vue&type=script&setup=true&lang.ts
var h = {
	key: 0,
	class: "space-y-3"
}, g = { key: 1 }, _ = { class: "flex items-center justify-between mb-4" }, v = { class: "text-sm font-semibold text-muted" }, y = ["onClick"], b = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, x = /* @__PURE__ */ c({
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
		let x = c, S = t(() => !x.store || !x.storeProp ? [] : x.store[x.storeProp]?.items || []), C = () => {
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
				consignment_number: "",
				consignment_date: "",
				party_inv_no: "",
				from_code: "",
				to_code: "",
				truck_no: "",
				pkg: "",
				weight: "",
				rate: 0,
				other_charge: 0,
				lr_charge: 0,
				dd_charge: 0,
				amount: 0
			};
			x.store[x.storeProp].items.push(e);
		}, w = (e, t) => {
			S.value.splice(t, 1);
		}, T = (e) => {
			let t = Number(e);
			return isNaN(t) ? 0 : t;
		}, E = (e) => {
			let t = T(e.rate), n = T(e.other_charge), r = T(e.lr_charge), i = T(e.dd_charge);
			return t + n + r + i;
		}, D = (e) => {
			let t = E(e);
			e.amount = t;
			let n = Math.round(t * 100);
			e.price = n, e.total = n, e.quantity = 1, e.name = e.consignment_number || "Consignment";
		};
		return p(S, (e) => {
			e.forEach((e) => D(e));
		}, {
			deep: !0,
			immediate: !0
		}), (t, p) => {
			let x = d("BaseContentPlaceholdersBox"), T = d("BaseContentPlaceholders"), D = d("BaseIcon"), O = d("BaseInput"), k = d("BaseInputGroup"), A = d("BaseCard");
			return l(), n(A, { class: "mb-6" }, {
				header: m(() => [...p[0] ||= [a("h3", { class: "text-base font-semibold text-heading" }, "Consignment Items", -1)]]),
				default: m(() => [c.isLoading ? (l(), i("div", h, [(l(), i(e, null, u(2, (e) => s(T, { key: e }, {
					default: m(() => [s(x, {
						rounded: !0,
						class: "w-full",
						style: { "min-height": "60px" }
					})]),
					_: 1
				})), 64))])) : (l(), i("div", g, [(l(!0), i(e, null, u(S.value, (e, t) => (l(), i("div", {
					key: e.id ?? t,
					class: "border border-line-light rounded-lg p-4 mb-4 relative"
				}, [a("div", _, [a("span", v, "Item " + f(t + 1), 1), S.value.length > 1 ? (l(), i("button", {
					key: 0,
					type: "button",
					class: "text-alert-error-text hover:text-alert-error-text",
					onClick: (n) => w(e.id, t)
				}, [s(D, {
					name: "TrashIcon",
					class: "h-5 w-5"
				})], 8, y)) : r("", !0)]), a("div", b, [
					s(k, {
						label: "Consignment No",
						required: ""
					}, {
						default: m(() => [s(O, {
							modelValue: e.consignment_number,
							"onUpdate:modelValue": (t) => e.consignment_number = t,
							type: "text",
							placeholder: "Enter consignment number"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(k, { label: "Consignment Date" }, {
						default: m(() => [s(O, {
							modelValue: e.consignment_date,
							"onUpdate:modelValue": (t) => e.consignment_date = t,
							type: "date"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(k, { label: "Party Inv No" }, {
						default: m(() => [s(O, {
							modelValue: e.party_inv_no,
							"onUpdate:modelValue": (t) => e.party_inv_no = t,
							type: "text",
							placeholder: "Party invoice number"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(k, { label: "From" }, {
						default: m(() => [s(O, {
							modelValue: e.from_code,
							"onUpdate:modelValue": (t) => e.from_code = t,
							type: "text",
							placeholder: "Origin code"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(k, { label: "Destination" }, {
						default: m(() => [s(O, {
							modelValue: e.to_code,
							"onUpdate:modelValue": (t) => e.to_code = t,
							type: "text",
							placeholder: "Destination code"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(k, { label: "Vehicle No" }, {
						default: m(() => [s(O, {
							modelValue: e.truck_no,
							"onUpdate:modelValue": (t) => e.truck_no = t,
							type: "text",
							placeholder: "Truck/vehicle number"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(k, { label: "Pkg" }, {
						default: m(() => [s(O, {
							modelValue: e.pkg,
							"onUpdate:modelValue": (t) => e.pkg = t,
							type: "text",
							placeholder: "Package type"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(k, { label: "Weight" }, {
						default: m(() => [s(O, {
							modelValue: e.weight,
							"onUpdate:modelValue": (t) => e.weight = t,
							type: "text",
							placeholder: "Shipment weight"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(k, { label: "Rate" }, {
						default: m(() => [s(O, {
							modelValue: e.rate,
							"onUpdate:modelValue": (t) => e.rate = t,
							modelModifiers: { number: !0 },
							type: "number",
							placeholder: "Freight rate"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(k, { label: "Other Charge" }, {
						default: m(() => [s(O, {
							modelValue: e.other_charge,
							"onUpdate:modelValue": (t) => e.other_charge = t,
							modelModifiers: { number: !0 },
							type: "number",
							placeholder: "0.00"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(k, { label: "LR Charge" }, {
						default: m(() => [s(O, {
							modelValue: e.lr_charge,
							"onUpdate:modelValue": (t) => e.lr_charge = t,
							modelModifiers: { number: !0 },
							type: "number",
							placeholder: "0.00"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(k, { label: "DD Charge" }, {
						default: m(() => [s(O, {
							modelValue: e.dd_charge,
							"onUpdate:modelValue": (t) => e.dd_charge = t,
							modelModifiers: { number: !0 },
							type: "number",
							placeholder: "0.00"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					s(k, {
						label: "Amount",
						class: "md:col-span-2 xl:col-span-1"
					}, {
						default: m(() => [s(O, {
							"model-value": E(e),
							disabled: "",
							class: "bg-surface-muted font-semibold"
						}, null, 8, ["model-value"])]),
						_: 2
					}, 1024)
				])]))), 128)), a("button", {
					type: "button",
					class: "flex items-center justify-center w-full px-6 py-3 text-base border border-dashed border-line-default rounded-lg cursor-pointer text-primary-500 hover:bg-hover",
					onClick: C
				}, [s(D, {
					name: "PlusIcon",
					class: "h-5 w-5 mr-2"
				}), p[1] ||= o(" Add Item ", -1)])]))]),
				_: 1
			});
		};
	}
}), S = {
	key: 0,
	class: "space-y-6 mt-5"
}, C = /* @__PURE__ */ c({
	__name: "OfficeInvoiceFormFields",
	props: {
		templateName: {},
		store: {}
	},
	setup(e) {
		let n = e, a = t(() => n.store?.newInvoice), o = t(() => n.templateName === "invoice_receipt");
		return (t, n) => o.value && a.value ? (l(), i("div", S, [s(x, {
			"is-loading": !1,
			store: e.store,
			"store-prop": "newInvoice"
		}, null, 8, ["store"])])) : r("", !0);
	}
});
//#endregion
//#region resources/js/init.ts
window.InvoiceShelf.booting((e, t, n) => {
	n.addMessages({ en: { invoice_receipt: {
		title: "Invoice Receipts",
		gst_tax_through: "GST Tax Through",
		invoice_receipt_settings: "Invoice Receipt Settings"
	} } }), n.registerInvoiceFormSection({
		id: "office-invoice-fields",
		component: C
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
		listLink: "/admin/invoices?view=invoice_receipt"
	});
});
//#endregion
