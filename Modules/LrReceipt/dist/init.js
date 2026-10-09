const { Fragment: e, computed: t, createCommentVNode: n, createElementBlock: r, createElementVNode: i, createVNode: a, defineComponent: o, onMounted: s, openBlock: c, ref: l, renderList: u, resolveComponent: d, toDisplayString: f, watch: p, withCtx: m } = window.__invoiceshelf_vue;
//#region resources/js/components/LrReceiptFormFields.vue?vue&type=script&setup=true&lang.ts
var h = {
	key: 0,
	class: "space-y-6 mt-5"
}, g = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, _ = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, v = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, y = /* @__PURE__ */ o({
	__name: "LrReceiptFormFields",
	props: {
		templateName: {},
		store: {}
	},
	setup(e) {
		let o = e, s = t(() => o.store?.newInvoice), l = t(() => o.templateName === "lr_receipt"), u = [
			"tr_basic_freight",
			"tr_local_collection",
			"tr_door_delivery",
			"tr_hamali",
			"tr_docket_charge",
			"tr_other_charge",
			"tr_fov"
		];
		function f(e) {
			let t = s.value?.[e];
			if (!t) return 0;
			let n = Number(t);
			return isNaN(n) ? 0 : n;
		}
		function y() {
			if (!s.value) return;
			let e = u.reduce((e, t) => e + f(t), 0);
			s.value.tr_net_amount = String(e), b(e);
		}
		function b(e) {
			let t = s.value?.items;
			if (!t || !Array.isArray(t)) return;
			let n = Math.round(e * 100);
			if (t.length > 0) {
				let e = t[0];
				e.name = e.name || "Freight", e.quantity = 1, e.price = n, e.total = n;
				return;
			}
			t.push({
				id: `tr-freight-${Date.now()}`,
				invoice_id: null,
				item_id: null,
				name: "Freight",
				description: "LR Receipt freight charges",
				quantity: 1,
				price: n,
				discount_type: "fixed",
				discount_val: 0,
				discount: 0,
				total: n,
				totalTax: 0,
				totalSimpleTax: 0,
				totalCompoundTax: 0,
				tax: 0,
				taxes: [],
				unit_name: null
			});
		}
		p(() => u.map((e) => s.value?.[e]), () => y(), { deep: !0 });
		let x = [
			"To Pay",
			"To Be Billed",
			"Paid"
		], S = ["Consignor", "Consignee"], C = ["YES", "NO"];
		return (e, t) => {
			let o = d("BaseInput"), u = d("BaseInputGroup"), f = d("BaseSelectInput"), p = d("BaseCard"), y = d("BaseTextarea");
			return l.value && s.value ? (c(), r("div", h, [
				a(p, { class: "mb-4" }, {
					header: m(() => [...t[25] ||= [i("h3", { class: "text-base font-semibold text-heading" }, "Trip Details", -1)]]),
					default: m(() => [i("div", g, [
						a(u, { label: "From" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_from_name,
								"onUpdate:modelValue": t[0] ||= (e) => s.value.tr_from_name = e,
								placeholder: "Origin"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "To" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_to_name,
								"onUpdate:modelValue": t[1] ||= (e) => s.value.tr_to_name = e,
								placeholder: "Destination"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "Truck No" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_truck_no,
								"onUpdate:modelValue": t[2] ||= (e) => s.value.tr_truck_no = e,
								placeholder: "Vehicle number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "Mode of Payment" }, {
							default: m(() => [a(f, {
								modelValue: s.value.tr_mode_of_payment,
								"onUpdate:modelValue": t[3] ||= (e) => s.value.tr_mode_of_payment = e,
								options: x.map((e) => ({
									id: e,
									label: e
								})),
								"value-prop": "id",
								"label-key": "label",
								placeholder: "Select..."
							}, null, 8, ["modelValue", "options"])]),
							_: 1
						}),
						a(u, { label: "GST Payable By" }, {
							default: m(() => [a(f, {
								modelValue: s.value.tr_gst_payable_by,
								"onUpdate:modelValue": t[4] ||= (e) => s.value.tr_gst_payable_by = e,
								options: S.map((e) => ({
									id: e,
									label: e
								})),
								"value-prop": "id",
								"label-key": "label",
								placeholder: "Select..."
							}, null, 8, ["modelValue", "options"])]),
							_: 1
						}),
						a(u, { label: "Time" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_time,
								"onUpdate:modelValue": t[5] ||= (e) => s.value.tr_time = e,
								type: "time"
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				}),
				a(p, { class: "mb-4" }, {
					header: m(() => [...t[26] ||= [i("h3", { class: "text-base font-semibold text-heading" }, "Consignment Details", -1)]]),
					default: m(() => [i("div", _, [
						a(u, {
							label: "Description of Goods",
							class: "md:col-span-2 xl:col-span-3"
						}, {
							default: m(() => [a(y, {
								modelValue: s.value.tr_description_goods,
								"onUpdate:modelValue": t[6] ||= (e) => s.value.tr_description_goods = e,
								rows: "2"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "HSN Code" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_hsn_code,
								"onUpdate:modelValue": t[7] ||= (e) => s.value.tr_hsn_code = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "E-way Bill No" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_eway_bill_no,
								"onUpdate:modelValue": t[8] ||= (e) => s.value.tr_eway_bill_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "Actual Weight" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_actual_weight,
								"onUpdate:modelValue": t[9] ||= (e) => s.value.tr_actual_weight = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "Charged Weight" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_charged_weight,
								"onUpdate:modelValue": t[10] ||= (e) => s.value.tr_charged_weight = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "Party Invoice No." }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_party_invoice_no,
								"onUpdate:modelValue": t[11] ||= (e) => s.value.tr_party_invoice_no = e,
								placeholder: "Party invoice number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "No of Articles" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_no_of_articles,
								"onUpdate:modelValue": t[12] ||= (e) => s.value.tr_no_of_articles = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "Packing" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_packing,
								"onUpdate:modelValue": t[13] ||= (e) => s.value.tr_packing = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "Delivery At" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_delivery_at,
								"onUpdate:modelValue": t[14] ||= (e) => s.value.tr_delivery_at = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "Goods Value" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_goods_value,
								"onUpdate:modelValue": t[15] ||= (e) => s.value.tr_goods_value = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "POD Required" }, {
							default: m(() => [a(f, {
								modelValue: s.value.tr_pod_required,
								"onUpdate:modelValue": t[16] ||= (e) => s.value.tr_pod_required = e,
								options: C.map((e) => ({
									id: e,
									label: e
								})),
								"value-prop": "id",
								"label-key": "label",
								placeholder: "Select..."
							}, null, 8, ["modelValue", "options"])]),
							_: 1
						})
					])]),
					_: 1
				}),
				a(p, { class: "mb-4" }, {
					header: m(() => [...t[27] ||= [i("h3", { class: "text-base font-semibold text-heading" }, "Freight Details", -1)]]),
					default: m(() => [i("div", v, [
						a(u, { label: "Basic Freight" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_basic_freight,
								"onUpdate:modelValue": t[17] ||= (e) => s.value.tr_basic_freight = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "Hamali" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_hamali,
								"onUpdate:modelValue": t[18] ||= (e) => s.value.tr_hamali = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "FOV" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_fov,
								"onUpdate:modelValue": t[19] ||= (e) => s.value.tr_fov = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "Local Collection" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_local_collection,
								"onUpdate:modelValue": t[20] ||= (e) => s.value.tr_local_collection = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "Door Delivery" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_door_delivery,
								"onUpdate:modelValue": t[21] ||= (e) => s.value.tr_door_delivery = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "Docket Charge" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_docket_charge,
								"onUpdate:modelValue": t[22] ||= (e) => s.value.tr_docket_charge = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "Other Charge" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_other_charge,
								"onUpdate:modelValue": t[23] ||= (e) => s.value.tr_other_charge = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(u, { label: "Net Amount" }, {
							default: m(() => [a(o, {
								modelValue: s.value.tr_net_amount,
								"onUpdate:modelValue": t[24] ||= (e) => s.value.tr_net_amount = e,
								type: "number",
								readonly: ""
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				})
			])) : n("", !0);
		};
	}
}), b = {
	key: 0,
	class: "text-center py-8 text-muted"
}, x = {
	key: 1,
	class: "text-center py-8 text-muted"
}, S = {
	key: 2,
	class: "bg-surface border border-line-default rounded-xl overflow-hidden"
}, C = { class: "w-full text-sm" }, w = { class: "px-4 py-3 font-medium text-heading" }, T = { class: "px-4 py-3 text-body" }, E = { class: "px-4 py-3 text-body" }, D = { class: "px-4 py-3 text-body" }, O = {
	key: 0,
	class: "inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-50 text-primary-700"
}, k = {
	key: 1,
	class: "text-muted"
}, A = { class: "px-4 py-3 text-body" }, j = { class: "px-4 py-3" }, M = { class: "px-2 py-1 text-xs rounded-full bg-primary-50 text-primary-600" }, N = /* @__PURE__ */ o({
	__name: "CustomerLrReceiptsView",
	props: { client: {} },
	setup(t) {
		let n = t, a = l([]), o = l(!0), d = l(0);
		s(async () => {
			try {
				let e = await n.client.get("/api/v1/customer/lr-receipts");
				a.value = e.data.data || [], d.value = e.data.meta?.lrReceiptTotalCount || 0;
			} catch {
				a.value = [];
			} finally {
				o.value = !1;
			}
		});
		function p(e) {
			return e ? new Date(e).toLocaleDateString() : "—";
		}
		return (t, n) => (c(), r("div", null, [n[1] ||= i("div", { class: "mb-6" }, [i("h1", { class: "text-2xl font-bold text-heading" }, "My LR Receipts"), i("p", { class: "text-sm text-muted mt-1" }, "Your consignment dockets and their delivery status")], -1), o.value ? (c(), r("div", b, "Loading...")) : a.value.length === 0 ? (c(), r("div", x, " No LR Receipts found. ")) : (c(), r("div", S, [i("table", C, [n[0] ||= i("thead", { class: "bg-surface-secondary border-b border-line-default" }, [i("tr", null, [
			i("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Docket No"),
			i("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Consignor"),
			i("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Consignee"),
			i("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "GST Tax Payable By"),
			i("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Date"),
			i("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Status")
		])], -1), i("tbody", null, [(c(!0), r(e, null, u(a.value, (e) => (c(), r("tr", {
			key: e.id,
			class: "border-b border-line-light hover:bg-surface-secondary"
		}, [
			i("td", w, f(e.invoice_number), 1),
			i("td", T, f(e.customer?.name || "—"), 1),
			i("td", E, f(e.consigneeCustomer?.name || "—"), 1),
			i("td", D, [e.gst_tax_payable_by || e.tr_gst_payable_by ? (c(), r("span", O, f(e.gst_tax_payable_by || e.tr_gst_payable_by), 1)) : (c(), r("span", k, "—"))]),
			i("td", A, f(p(e.invoice_date)), 1),
			i("td", j, [i("span", M, f(e.status), 1)])
		]))), 128))])])]))]));
	}
});
//#endregion
//#region resources/js/init.ts
window.InvoiceShelf.booting((e, t, n) => {
	window.__lrReceiptClient = n.client, n.addMessages({ en: { lr_receipt: {
		title: "LR Receipts",
		docket_no: "Docket No.",
		consignor: "Consignor",
		consignee: "Consignee",
		trip_details: "Trip Details",
		consignment_details: "Consignment Details",
		freight_details: "Freight Details",
		from: "From",
		to: "To",
		truck_no: "Truck No",
		mode_of_payment: "Mode of Payment",
		gst_payable_by: "GST Payable By",
		description_of_goods: "Description of Goods",
		hsn_code: "HSN Code",
		eway_bill_no: "E-way Bill No",
		actual_weight: "Actual Weight",
		charged_weight: "Charged Weight",
		party_invoice_no: "Party Invoice No.",
		no_of_articles: "No of Articles",
		packing: "Packing",
		basic_freight: "Basic Freight",
		hamali: "Hamali",
		fov: "FOV",
		local_collection: "Local Collection",
		door_delivery: "Door Delivery",
		docket_charge: "Docket Charge",
		other_charge: "Other Charge",
		net_amount: "Net Amount",
		auto_fill: "Auto-Fill from Photo",
		my_receipts: "My LR Receipts"
	} } }), n.registerInvoiceFormSection({
		id: "lr-receipt-fields",
		component: y
	}), n.registerInvoiceViewMode({
		id: "lr-receipt-view-mode",
		value: "lr_receipt",
		label: "LR Receipt",
		icon: "ClipboardDocumentListIcon",
		createLink: "invoices/create?template=lr_receipt",
		listLink: "/admin/invoices?view=lr_receipt",
		ability: "lr-receipt:view-lr-receipt"
	}), n.registerInvoiceDocumentMeta({
		id: "lr-receipt-doc-meta",
		templateName: "lr_receipt",
		label: "LR Receipt",
		labelPlural: "LR Receipts",
		listLink: "/admin/invoices?view=lr_receipt",
		dateLabel: "Docket Date",
		numberLabel: "Docket No."
	}), n.registerPage({
		id: "customer-lr-receipts",
		module: "lr-receipt",
		path: "customer",
		component: N,
		meta: { title: "My LR Receipts" }
	});
});
//#endregion
