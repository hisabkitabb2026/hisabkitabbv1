const { Fragment: e, computed: t, createBlock: n, createCommentVNode: r, createElementBlock: i, createElementVNode: a, createTextVNode: o, createVNode: s, defineComponent: c, openBlock: l, renderList: u, resolveComponent: d, toDisplayString: f, watch: p, withCtx: m } = window.__invoiceshelf_vue;
//#region resources/js/components/QuotationItemsTable.vue?vue&type=script&setup=true&lang.ts
var h = {
	key: 0,
	class: "space-y-3"
}, g = { key: 1 }, _ = { class: "flex items-center justify-between mb-4" }, v = { class: "text-sm font-semibold text-muted" }, y = ["onClick"], b = { class: "grid gap-x-4 gap-y-4 grid-cols-2 md:grid-cols-3 xl:grid-cols-4" }, x = /* @__PURE__ */ c({
	__name: "QuotationItemsTable",
	props: {
		isLoading: {
			type: Boolean,
			default: !1
		},
		itemValidationScope: { default: "newEstimate" },
		store: {},
		storeProp: { default: "newEstimate" },
		isEdit: { type: Boolean }
	},
	setup(c) {
		let x = c, S = [
			{
				key: "tr_rate_9mt",
				label: "9 mt"
			},
			{
				key: "tr_rate_10mt",
				label: "10 mt"
			},
			{
				key: "tr_rate_12mt",
				label: "12 mt"
			},
			{
				key: "tr_rate_15mt",
				label: "15 mt"
			},
			{
				key: "tr_rate_18mt",
				label: "18 mt"
			},
			{
				key: "tr_rate_24mt",
				label: "24 mt"
			},
			{
				key: "tr_rate_30mt",
				label: "30 mt"
			}
		], C = t(() => !x.store || !x.storeProp ? [] : x.store[x.storeProp]?.items || []), w = () => {
			let e = {
				id: Math.random().toString(36).substr(2, 9),
				name: "",
				quantity: 1,
				price: 0,
				total: 0,
				discount: 0,
				discount_type: "fixed",
				discount_val: 0,
				tax: 0,
				taxes: [],
				tr_station_name: "",
				amount: 0
			};
			for (let t of S) e[t.key] = 0;
			x.store[x.storeProp].items.push(e);
		}, T = (e, t) => {
			C.value.splice(t, 1);
		}, E = (e) => {
			e.name = e.tr_station_name || "Station", e.quantity = 1, e.price = 0, e.total = 0, e.tax = 0;
		};
		return p(C, (e) => {
			e.forEach((e) => E(e));
		}, {
			deep: !0,
			immediate: !0
		}), (t, p) => {
			let x = d("BaseContentPlaceholdersBox"), E = d("BaseContentPlaceholders"), D = d("BaseIcon"), O = d("BaseInput"), k = d("BaseInputGroup"), A = d("BaseCard");
			return l(), n(A, { class: "mb-6" }, {
				header: m(() => [...p[0] ||= [a("h3", { class: "text-base font-semibold text-heading" }, "Quotation Items", -1)]]),
				default: m(() => [c.isLoading ? (l(), i("div", h, [(l(), i(e, null, u(2, (e) => s(E, { key: e }, {
					default: m(() => [s(x, {
						rounded: !0,
						class: "w-full",
						style: { "min-height": "60px" }
					})]),
					_: 1
				})), 64))])) : (l(), i("div", g, [(l(!0), i(e, null, u(C.value, (t, n) => (l(), i("div", {
					key: t.id ?? n,
					class: "border border-line-light rounded-lg p-4 mb-4 relative"
				}, [
					a("div", _, [a("span", v, "Item " + f(n + 1), 1), C.value.length > 1 ? (l(), i("button", {
						key: 0,
						type: "button",
						class: "text-alert-error-text hover:text-alert-error-text",
						onClick: (e) => T(t.id, n)
					}, [s(D, {
						name: "TrashIcon",
						class: "h-5 w-5"
					})], 8, y)) : r("", !0)]),
					s(k, {
						label: "Station Name",
						required: "",
						class: "mb-4"
					}, {
						default: m(() => [s(O, {
							modelValue: t.tr_station_name,
							"onUpdate:modelValue": (e) => t.tr_station_name = e,
							type: "text",
							placeholder: "Enter station name"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1024),
					a("div", b, [(l(), i(e, null, u(S, (e) => s(k, {
						key: e.key,
						label: e.label
					}, {
						default: m(() => [s(O, {
							modelValue: t[e.key],
							"onUpdate:modelValue": (n) => t[e.key] = n,
							modelModifiers: { number: !0 },
							type: "number",
							placeholder: "0.00"
						}, null, 8, ["modelValue", "onUpdate:modelValue"])]),
						_: 2
					}, 1032, ["label"])), 64))])
				]))), 128)), a("button", {
					type: "button",
					class: "inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-primary-600 border border-line-default rounded-lg hover:bg-surface-secondary transition-colors",
					onClick: w
				}, [s(D, {
					name: "PlusIcon",
					class: "h-4 w-4"
				}), p[1] ||= o(" Add Item ", -1)])]))]),
				_: 1
			});
		};
	}
}), S = {
	key: 0,
	class: "space-y-6 mt-5"
}, C = /* @__PURE__ */ c({
	__name: "QuotationFormFields",
	props: {
		templateName: {},
		store: {}
	},
	setup(e) {
		let n = e, a = t(() => n.store?.newEstimate), o = t(() => n.templateName === "quotation");
		return (t, n) => o.value && a.value ? (l(), i("div", S, [s(x, {
			"is-loading": !1,
			store: e.store,
			"store-prop": "newEstimate"
		}, null, 8, ["store"])])) : r("", !0);
	}
});
//#endregion
//#region resources/js/init.ts
window.InvoiceShelf.booting((e, t, n) => {
	n.addMessages({ en: { quotation: {
		title: "Quotations",
		quotation_items: "Quotation Items",
		station_name: "Station Name",
		capacity: "Capacity",
		rate: "Rate",
		amount: "Amount",
		add_item: "Add Item",
		quotation_settings: "Quotation Settings"
	} } }), n.registerEstimateFormSection({
		id: "quotation-fields",
		component: C
	}), n.registerEstimateViewMode({
		id: "quotation-view-mode",
		value: "quotation",
		label: "Quotation",
		icon: "DocumentTextIcon",
		createLink: "estimates/create?template=quotation",
		listLink: "/admin/estimates?view=quotation",
		ability: "quotation:view-quotation"
	}), n.registerEstimateDocumentMeta({
		id: "quotation-doc-meta",
		templateName: "quotation",
		label: "Quotation",
		labelPlural: "Quotations",
		listLink: "/admin/estimates?view=quotation",
		dateLabel: "Quotation Date",
		numberLabel: "Quotation No."
	});
});
//#endregion
