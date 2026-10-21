const { Fragment: e, computed: t, createBlock: n, createCommentVNode: r, createElementBlock: i, createElementVNode: a, createTextVNode: o, createVNode: s, defineComponent: c, getCurrentInstance: l, h: u, normalizeClass: d, normalizeStyle: f, onBeforeUnmount: p, onMounted: m, onUnmounted: h, openBlock: g, ref: _, renderList: v, resolveComponent: y, toDisplayString: b, unref: x, vModelSelect: S, watch: C, withCtx: w, withDirectives: T, withModifiers: E } = window.__invoiceshelf_vue;
//#region resources/js/messages.ts
var D = { en: { trips: {
	title: "Trips",
	new_trip: "New Trip",
	search_placeholder: "Search route, lorry, goods...",
	filters: "Filters",
	customer: "Customer",
	all_customers: "All customers",
	status: "Status",
	all_statuses: "All statuses",
	unbilled_only: "Unbilled only",
	board: "Board",
	list: "List",
	empty_title: "No trips yet",
	empty_description: "Your board is empty. Create your first trip by mapping an existing LR Receipt, or by filling a new one.",
	map_existing: "Map existing LRs",
	create_new: "Create new LR",
	unlinked_lrs: "Unlinked LR Receipts",
	no_unlinked: "No unlinked LR receipts. Create one first, or use the Create tab.",
	selected: "Selected: {count} LR(s) · {amount}",
	mixed_routes: "Different routes selected — same vehicle?",
	create_trip: "Create Trip",
	create_trip_count: "Create Trip ({count})",
	customer_field: "Customer",
	from: "From",
	to: "To",
	goods: "Goods",
	weight: "Weight",
	pickup_date: "Pickup date",
	basic_freight: "Basic freight",
	hamali: "Hamali",
	door_delivery: "Door delivery",
	other_charge: "Other charge",
	net_amount: "Net amount",
	creating: "Creating...",
	trip_no: "Trip",
	cancelled: "Cancelled",
	open: "Open trip",
	cancel_trip: "Cancel trip",
	cancel_confirm: "Cancel this trip? It stays on the board, greyed out, and can never be billed.",
	move_to: "Move to",
	trip_detail: {
		documents: "Documents",
		money: "The Money",
		lr_receipts: "LR Receipts",
		no_lr_receipts: "No LR receipts linked yet.",
		map_another: "Map another LR",
		lorry_receipt: "Lorry Receipt",
		map_lorry: "Map existing",
		create_lorry: "Create in Lorry Receipts",
		view: "View",
		unlink: "Unlink",
		revenue: "Revenue",
		cost: "Cost",
		profit: "Profit",
		owner_paid: "Owner paid",
		owner_balance: "Owner balance",
		record_payment: "Record owner payment",
		amount: "Amount",
		method: "Method",
		note: "Note",
		date: "Date",
		cancel_trip: "Cancel Trip",
		cancelled: "This trip is cancelled."
	},
	load_failed: "Unable to load the board.",
	trip_created: "Trip #{number} created.",
	trip_cancelled: "Trip cancelled.",
	move_failed: "Unable to move the trip.",
	save_failed: "Unable to save the trip.",
	create_failed: "Unable to create the trip.",
	link_failed: "Unable to link the receipt.",
	unbilled_amount: "Unbilled amount",
	trips_this_month: "trips"
} } }, O = "trips", k = `/admin/modules/${O}`, A = {
	board: k,
	list: `${k}/list`,
	week: `${k}/week`,
	new: `${k}/new`,
	trip: (e) => `${k}/${e}`,
	reports: `${k}/reports`,
	invoiceView: (e) => `/admin/invoices/${e}/view`,
	invoiceCreate: (e) => `/admin/invoices/create?template=${e}`
}, j = {
	trips: `extension.page.${O}.trips`,
	board: `extension.page.${O}.trips.board`,
	list: `extension.page.${O}.trips.list`,
	week: `extension.page.${O}.trips.week`,
	trip: `extension.page.${O}.trip`,
	reports: `extension.page.${O}.reports`
};
function M(e, t) {
	return c({ setup: (n, { attrs: r }) => () => u(t, {
		...r,
		client: e.client,
		notify: (t, n) => {
			e.notify(t, n);
		},
		router: e.router
	}) });
}
//#endregion
//#region resources/js/support/i18n.ts
function N() {
	return l()?.appContext.config.globalProperties.$t ?? ((e) => e);
}
//#endregion
//#region resources/js/support/useTripReportDownload.ts
function ee(e, t) {
	async function n() {
		let n = t();
		if (n) try {
			let t = await e.get(n + "&download=true", { responseType: "blob" }), r = URL.createObjectURL(t.data), i = document.createElement("a");
			i.href = r, i.download = "trip-report.pdf", document.body.appendChild(i), i.click(), document.body.removeChild(i), setTimeout(() => URL.revokeObjectURL(r), 5e3);
		} catch {}
	}
	return () => {
		n();
	};
}
//#endregion
//#region resources/js/support/date-range.ts
var P = (e) => e.getFullYear() + "-" + String(e.getMonth() + 1).padStart(2, "0") + "-" + String(e.getDate()).padStart(2, "0");
function te(e) {
	let t = new Date(e);
	return t.setHours(0, 0, 0, 0), t;
}
function ne(e) {
	let t = new Date(e);
	return t.setHours(23, 59, 59, 999), t;
}
function re(e) {
	let t = te(e), n = (t.getDay() + 6) % 7;
	return t.setDate(t.getDate() - n), t;
}
function ie(e) {
	let t = re(e), n = new Date(t);
	return n.setDate(t.getDate() + 6), ne(n);
}
function ae(e) {
	return te(new Date(e.getFullYear(), e.getMonth(), 1));
}
function oe(e) {
	return ne(new Date(e.getFullYear(), e.getMonth() + 1, 0));
}
function se(e) {
	let t = Math.floor(e.getMonth() / 3) * 3;
	return te(new Date(e.getFullYear(), t, 1));
}
function ce(e) {
	let t = Math.floor(e.getMonth() / 3) * 3;
	return ne(new Date(e.getFullYear(), t + 3, 0));
}
function le(e) {
	return te(new Date(e.getFullYear(), 0, 1));
}
function ue(e) {
	return ne(new Date(e.getFullYear(), 12, 0));
}
var de = {
	isoWeek: re,
	month: ae,
	quarter: se,
	year: le
}, fe = {
	isoWeek: ie,
	month: oe,
	quarter: ce,
	year: ue
};
function pe(e, t) {
	return {
		from: P(de[e](t)),
		to: P(fe[e](t))
	};
}
function me(e, t) {
	let n = new Date(t);
	return e === "isoWeek" ? n.setDate(n.getDate() - 7) : e === "month" ? n.setMonth(n.getMonth() - 1) : e === "quarter" ? n.setMonth(n.getMonth() - 3) : n.setFullYear(n.getFullYear() - 1), {
		from: P(de[e](n)),
		to: P(fe[e](n))
	};
}
function he() {
	return pe("month", /* @__PURE__ */ new Date());
}
function ge(e) {
	let t = /* @__PURE__ */ new Date();
	switch (e) {
		case "This Week": return pe("isoWeek", t);
		case "This Month": return pe("month", t);
		case "This Quarter": return pe("quarter", t);
		case "This Year": return pe("year", t);
		case "Previous Week": return me("isoWeek", t);
		case "Previous Month": return me("month", t);
		case "Previous Quarter": return me("quarter", t);
		case "Previous Year": return me("year", t);
		default: return {
			from: P(t),
			to: P(t)
		};
	}
}
//#endregion
//#region resources/js/support/period.ts
function _e(e) {
	return [
		["Today", "dateRange.today"],
		["This Week", "dateRange.this_week"],
		["This Month", "dateRange.this_month"],
		["This Quarter", "dateRange.this_quarter"],
		["This Year", "dateRange.this_year"],
		["Previous Week", "dateRange.previous_week"],
		["Previous Month", "dateRange.previous_month"],
		["Previous Quarter", "dateRange.previous_quarter"],
		["Previous Year", "dateRange.previous_year"]
	].map(([t, n]) => ({
		key: t,
		label: e(n),
		range: () => ge(t)
	}));
}
function ve(e) {
	let t = e.range?.();
	return {
		preset: e.key,
		from: t?.from ?? null,
		to: t?.to ?? null
	};
}
//#endregion
//#region resources/js/components/TripReportPdfPane.vue?vue&type=script&setup=true&lang.ts
var ye = {
	key: 0,
	class: "flex items-center justify-center w-full h-screen border border-line-default border-solid rounded bg-surface"
}, be = {
	key: 1,
	class: "flex flex-col items-center justify-center gap-4 w-full h-screen border border-line-default border-solid rounded bg-surface"
}, xe = ["src"], Se = /* @__PURE__ */ c({
	__name: "TripReportPdfPane",
	props: {
		client: { type: [Function, Object] },
		path: {}
	},
	setup(e, { expose: t }) {
		let n = e, r = _("idle"), c = _(null), l = null, u = null, d = 0;
		function f() {
			c.value && URL.revokeObjectURL(c.value), c.value = null, l = null, u = null;
		}
		async function m(e) {
			let t = ++d;
			f(), r.value = "loading";
			try {
				let i = await n.client.get(e, { responseType: "blob" });
				if (t !== d) return;
				let a = i.data;
				if (a.type.startsWith("text/html") || a.type.startsWith("application/json")) throw Error("Server returned a message instead of a PDF");
				l = a, u = e, c.value = URL.createObjectURL(a), r.value = "ready";
			} catch {
				t === d && (r.value = "error");
			}
		}
		function h() {
			n.path && m(n.path);
		}
		async function v(e) {
			let t = e ?? n.path;
			if (t) {
				if (l && u === t) {
					let e = URL.createObjectURL(l);
					window.open(e, "_blank"), setTimeout(() => URL.revokeObjectURL(e), 3e4);
					return;
				}
				try {
					let e = await n.client.get(t, { responseType: "blob" }), r = URL.createObjectURL(e.data);
					window.open(r, "_blank"), setTimeout(() => URL.revokeObjectURL(r), 3e4);
				} catch {
					r.value = "error";
				}
			}
		}
		return C(() => n.path, (e) => {
			if (e) {
				m(e);
				return;
			}
			d++, f(), r.value = "idle";
		}, { immediate: !0 }), p(f), t({ view: v }), (e, t) => {
			let n = y("BaseSpinner"), l = y("BaseIcon"), u = y("BaseButton");
			return g(), i("div", null, [r.value === "loading" || r.value === "idle" ? (g(), i("div", ye, [s(n, { class: "w-8 h-8 text-primary-400" })])) : r.value === "error" ? (g(), i("div", be, [
				s(l, {
					name: "ExclamationCircleIcon",
					class: "w-12 h-12 text-muted"
				}),
				t[1] ||= a("p", { class: "text-sm text-muted" }, "Unable to load the report.", -1),
				s(u, {
					variant: "primary-outline",
					size: "sm",
					onClick: h
				}, {
					default: w(() => [...t[0] ||= [o("Retry", -1)]]),
					_: 1
				})
			])) : (g(), i("iframe", {
				key: 2,
				src: c.value ?? void 0,
				title: "Trip Report PDF",
				class: "w-full h-screen border border-line-default border-solid rounded bg-surface"
			}, null, 8, xe))]);
		};
	}
}), Ce = { class: "grid gap-8 md:grid-cols-12 pt-10" }, we = { class: "col-span-8 md:col-span-4" }, Te = { class: "hidden mt-6 md:block" }, Ee = { class: "col-span-8" }, De = /* @__PURE__ */ c({
	__name: "TripReportsPage",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {}
	},
	setup(e) {
		let r = e, i = _e(N()), c = _(ve(i[2])), l = [
			{
				label: "By Customer Name",
				value: "customer"
			},
			{
				label: "By LR No",
				value: "lr"
			},
			{
				label: "By Invoice No",
				value: "invoice"
			},
			{
				label: "By Supplier Name",
				value: "supplier"
			}
		], u = _("customer"), f = _(null), p = _(null), h = he(), v = _(h.from), b = _(h.to), S = _(""), T = _(""), D = _(""), O = _(""), k = t(() => f.value), j = t(() => {
			let e = "/api/v1/trips/reports/pdf?from_date=" + v.value + "&to_date=" + b.value + "&type=" + u.value;
			return S.value.trim() && (e += "&customer=" + encodeURIComponent(S.value.trim())), T.value.trim() && (e += "&lr_no=" + encodeURIComponent(T.value.trim())), D.value.trim() && (e += "&invoice_no=" + encodeURIComponent(D.value.trim())), O.value.trim() && (e += "&supplier=" + encodeURIComponent(O.value.trim())), e;
		}), M = ee(r.client, () => (f.value = j.value, f.value));
		m(() => {
			f.value = j.value;
		}), C(c, (e) => {
			e.from && e.to && (v.value = e.from, b.value = e.to);
		}), C([
			v,
			b,
			u,
			S,
			T,
			D,
			O
		], () => {
			f.value = j.value;
		});
		function P() {
			return f.value = j.value, !0;
		}
		function te() {
			P(), p.value?.view(f.value);
		}
		function ne() {
			r.router.push(A.board);
		}
		return (t, r) => {
			let f = y("BaseBreadcrumbItem"), m = y("BaseBreadcrumb"), h = y("BaseIcon"), _ = y("BaseButton"), v = y("BasePageHeader"), b = y("BasePeriodPicker"), C = y("BaseInputGroup"), j = y("BaseMultiselect"), N = y("BaseInput"), ee = y("BasePage");
			return g(), n(ee, null, {
				default: w(() => [s(v, { title: "Trip Reports" }, {
					actions: w(() => [s(_, {
						variant: "white",
						onClick: ne
					}, {
						left: w((e) => [s(h, {
							name: "ArrowLeftIcon",
							class: d(e.class)
						}, null, 8, ["class"])]),
						default: w(() => [r[6] ||= o(" Back to Trips ", -1)]),
						_: 1
					}), s(_, {
						variant: "primary",
						class: "ms-4",
						onClick: x(M)
					}, {
						left: w((e) => [s(h, {
							name: "ArrowDownTrayIcon",
							class: d(e.class)
						}, null, 8, ["class"])]),
						default: w(() => [r[7] ||= o(" Download PDF ", -1)]),
						_: 1
					}, 8, ["onClick"])]),
					default: w(() => [s(m, null, {
						default: w(() => [
							s(f, {
								title: "Dashboard",
								to: "/admin/dashboard"
							}),
							s(f, {
								title: "Trips",
								to: x(A).board
							}, null, 8, ["to"]),
							s(f, {
								title: "Reports",
								to: "#",
								active: ""
							})
						]),
						_: 1
					})]),
					_: 1
				}), a("div", Ce, [a("div", we, [
					s(C, {
						label: "Date Range",
						class: "mb-6"
					}, {
						default: w(() => [s(b, {
							modelValue: c.value,
							"onUpdate:modelValue": r[0] ||= (e) => c.value = e,
							presets: x(i),
							block: "",
							position: "bottom-start"
						}, null, 8, ["modelValue", "presets"])]),
						_: 1
					}),
					s(C, {
						label: "Report Type",
						class: "col-span-12 md:col-span-8"
					}, {
						default: w(() => [s(j, {
							modelValue: u.value,
							"onUpdate:modelValue": [r[1] ||= (e) => u.value = e, P],
							options: l,
							placeholder: "Select report type",
							class: "mt-1"
						}, null, 8, ["modelValue"])]),
						_: 1
					}),
					s(C, {
						label: "Customer Name",
						class: "col-span-12 md:col-span-8 mt-4"
					}, {
						default: w(() => [s(N, {
							modelValue: S.value,
							"onUpdate:modelValue": [r[2] ||= (e) => S.value = e, P],
							placeholder: "Type customer name..."
						}, null, 8, ["modelValue"])]),
						_: 1
					}),
					s(C, {
						label: "LR No",
						class: "col-span-12 md:col-span-8"
					}, {
						default: w(() => [s(N, {
							modelValue: T.value,
							"onUpdate:modelValue": [r[3] ||= (e) => T.value = e, P],
							placeholder: "Type LR number..."
						}, null, 8, ["modelValue"])]),
						_: 1
					}),
					s(C, {
						label: "Invoice No",
						class: "col-span-12 md:col-span-8"
					}, {
						default: w(() => [s(N, {
							modelValue: D.value,
							"onUpdate:modelValue": [r[4] ||= (e) => D.value = e, P],
							placeholder: "Type invoice number..."
						}, null, 8, ["modelValue"])]),
						_: 1
					}),
					s(C, {
						label: "Supplier Name",
						class: "col-span-12 md:col-span-8"
					}, {
						default: w(() => [s(N, {
							modelValue: O.value,
							"onUpdate:modelValue": [r[5] ||= (e) => O.value = e, P],
							placeholder: "Type supplier name..."
						}, null, 8, ["modelValue"])]),
						_: 1
					}),
					a("div", Te, [s(_, {
						variant: "primary",
						class: "justify-center w-full",
						type: "submit",
						onClick: E(P, ["prevent"])
					}, {
						left: w((e) => [s(h, {
							name: "ArrowPathIcon",
							class: d(e.class)
						}, null, 8, ["class"])]),
						default: w(() => [r[8] ||= o(" Update Report ", -1)]),
						_: 1
					})])
				]), a("div", Ee, [s(Se, {
					ref_key: "pdfPane",
					ref: p,
					client: e.client,
					path: k.value,
					class: "hidden md:block"
				}, null, 8, ["client", "path"]), a("button", {
					type: "button",
					class: "flex items-center justify-center w-full gap-2 px-5 text-sm font-medium transition-colors rounded-lg h-11 md:hidden bg-btn-primary text-on-primary hover:bg-btn-primary-hover",
					onClick: te
				}, [s(h, {
					name: "DocumentTextIcon",
					class: "w-5 h-5"
				}), r[9] ||= o(" View PDF ", -1)])])])]),
				_: 1
			});
		};
	}
}), Oe = `${O}:view-trip`;
function ke(e) {
	e.registerPage({
		id: "reports",
		module: O,
		path: "reports",
		component: M(e, De),
		meta: {
			ability: Oe,
			title: "Trip Reports"
		}
	});
}
//#endregion
//#region resources/js/api/trips.ts
var F = "/api/v1/trips";
async function Ae(e, t = {}) {
	let { data: n } = await e.get(F, { params: t });
	return n.data;
}
async function je(e) {
	let { data: t } = await e.get(`${F}/statuses`);
	return t.data;
}
async function Me(e, t) {
	let { data: n } = await e.get(`${F}/${t}`);
	return n.data;
}
async function Ne(e, t) {
	let { data: n } = await e.post(`${F}/from-lrs`, { invoice_ids: t });
	return n.data;
}
async function Pe(e, t, n, r) {
	let { data: i } = await e.post(`${F}/${t}/move`, {
		status_id: n,
		position: r
	});
	return i.data;
}
async function Fe(e, t) {
	let { data: n } = await e.post(`${F}/${t}/cancel`);
	return n.data;
}
async function Ie(e, t, n) {
	let { data: r } = await e.patch(`${F}/${t}/status`, { status_id: n });
	return r.data;
}
async function Le(e, t = "lr", n = null, r = null, i = !1) {
	let { data: a } = await e.get(`${F}/unlinked-lrs`, { params: {
		type: t,
		customer_id: n,
		trip_id: r,
		all: i ? 1 : void 0
	} });
	return a.data;
}
async function Re(e, t, n, r) {
	let { data: i } = await e.post(`${F}/${t}/receipts`, {
		invoice_id: n,
		type: r
	});
	return i.data;
}
async function ze(e, t) {
	let { data: n } = await e.post(`${F}/${t}/fetch-lorry-receipt`);
	return n.data;
}
async function Be(e, t) {
	let { data: n } = await e.post(`${F}/${t}/fetch-invoice-receipt`);
	return n.data;
}
async function Ve(e, t, n, r) {
	let i = new FormData();
	i.append("file", n), i.append("category", r);
	let { data: a } = await e.post(`${F}/${t}/pod`, i, { headers: { "Content-Type": "multipart/form-data" } });
	return a.data;
}
async function He(e, t, n) {
	let { data: r } = await e.delete(`${F}/${t}/pod/${n}`);
	return r.data;
}
async function Ue(e, t, n) {
	let { data: r } = await e.get(`${F}/${t}/pod/${n}`, { responseType: "blob" });
	return r;
}
async function We(e, t, n) {
	let { data: r } = await e.delete(`${F}/${t}/receipts/${n}`);
	return r.data;
}
//#endregion
//#region resources/js/support/http.ts
function I(e, t) {
	if (typeof e == "object" && e) {
		let t = e.response?.data;
		if (typeof t == "object" && t) {
			let e = t.errors;
			if (typeof e == "object" && e) {
				let t = Object.values(e)[0];
				if (Array.isArray(t) && typeof t[0] == "string") return t[0];
			}
			let n = t.message;
			if (typeof n == "string" && n !== "") return n;
		}
	}
	return t;
}
//#endregion
//#region resources/js/support/format.ts
function L(e) {
	return ((e ?? 0) / 100).toLocaleString(void 0, {
		minimumFractionDigits: 2,
		maximumFractionDigits: 2
	});
}
//#endregion
//#region resources/js/pages/TripCreatePage.vue?vue&type=script&setup=true&lang.ts
var Ge = { class: "max-w-md" }, Ke = { class: "mt-2 flex items-center justify-between text-xs text-muted" }, qe = { class: "mt-5 border-t border-line-light pt-5" }, Je = { class: "mb-4 flex flex-wrap items-center justify-between gap-3" }, Ye = { class: "flex gap-2" }, Xe = {
	key: 0,
	class: "mb-3 flex items-center gap-2 rounded-lg border border-primary-200 bg-primary-50 p-2.5 text-xs text-primary-800"
}, Ze = { class: "font-semibold" }, Qe = {
	key: 1,
	class: "mb-3 flex items-center gap-2 rounded-lg border border-line-light bg-surface-secondary p-2 text-xs text-muted"
}, $e = { class: "text-heading" }, et = {
	key: 2,
	class: "rounded-lg bg-surface-secondary p-4 text-sm text-muted"
}, tt = {
	key: 3,
	class: "rounded-lg bg-surface-secondary p-4 text-sm text-muted",
	role: "status"
}, nt = {
	key: 4,
	class: "rounded-lg bg-surface-secondary p-4 text-sm text-muted",
	role: "status"
}, rt = {
	key: 5,
	class: "rounded-lg bg-surface-secondary p-4 text-sm text-muted",
	role: "status"
}, it = {
	key: 6,
	class: "space-y-3"
}, at = { class: "flex h-[38px] items-center rounded-lg bg-surface-tertiary px-3 text-sm font-medium text-heading" }, ot = { class: "justify-self-end" }, st = {
	key: 7,
	class: "mt-3 text-sm text-alert-warning-text"
}, ct = { class: "mt-4 flex flex-wrap justify-end gap-x-8 gap-y-2 border-t border-line-light pt-4 text-sm" }, lt = { class: "text-muted" }, ut = { class: "font-semibold text-heading" }, dt = { class: "text-muted" }, ft = { class: "font-semibold text-heading" }, pt = { class: "mt-5 border-t border-line-light pt-5" }, mt = { class: "grid grid-cols-2 gap-3 text-center" }, ht = { class: "rounded-lg bg-surface-secondary p-3" }, gt = { class: "text-lg font-semibold text-heading" }, _t = { class: "rounded-lg bg-surface-secondary p-3" }, vt = { class: "text-lg font-semibold text-heading" }, yt = /* @__PURE__ */ c({
	__name: "TripCreatePage",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {}
	},
	setup(c) {
		let l = c, u = _(null), f = _(!1), p = _([]), m = _([]), h = _(!1), S = _(!1);
		async function T() {
			if (!u.value && !f.value) {
				p.value = [], m.value = [];
				return;
			}
			h.value = !0;
			try {
				p.value = await Le(l.client, "lr", u.value);
			} catch (e) {
				l.notify("error", I(e, "Unable to load the receipts.")), p.value = [];
			} finally {
				h.value = !1;
			}
		}
		C(u, (e, t) => {
			t !== void 0 && e !== t && (m.value = []), e && (f.value = !1), T();
		});
		function D() {
			f.value = !f.value, f.value && (u.value = null), m.value = [], T();
		}
		function O(e) {
			if (!e) return;
			let t = typeof e == "object" && e ? e.id : e, n = Number(t);
			if (!(!n || isNaN(n))) return p.value.find((e) => e.id === n);
		}
		function k(e) {
			return O(e)?.amount ?? 0;
		}
		function j(e) {
			let t = e ? Number(typeof e == "object" ? e.id : e) : null, n = m.value.map((e) => {
				let t = typeof e.receipt_id == "object" && e.receipt_id !== null ? e.receipt_id.id : e.receipt_id;
				return t ? Number(t) : null;
			}).filter((e) => e !== null && e !== t);
			return p.value.filter((e) => !n.includes(e.id)).map((e) => ({
				id: e.id,
				label: `${e.invoice_number} · ${e.from_city ?? "—"} → ${e.to_city ?? "—"} · ${e.customer_name ?? "No customer"} (${L(e.amount)})`
			}));
		}
		let M = t(() => m.value.map((e) => {
			let t = typeof e.receipt_id == "object" && e.receipt_id !== null ? e.receipt_id.id : e.receipt_id;
			return t ? Number(t) : null;
		}).filter((e) => e !== null)), N = t(() => m.value.map((e) => O(e.receipt_id)).filter((e) => e !== void 0)), ee = t(() => {
			let e = N.value.map((e) => e.customer_name).filter((e) => !!(e && e.trim()));
			return [...new Set(e)];
		}), P = t(() => N.value.reduce((e, t) => e + t.amount, 0)), te = t(() => new Set(N.value.map((e) => `${e.from_city}→${e.to_city}`)).size > 1);
		function ne() {
			m.value.push({ receipt_id: null });
		}
		function re(e) {
			m.value.splice(e, 1);
		}
		function ie() {
			m.value = [...p.value].sort((e, t) => (e.invoice_date ?? "9999-12-31").localeCompare(t.invoice_date ?? "9999-12-31") || e.id - t.id).map((e) => ({ receipt_id: e.id }));
		}
		async function ae() {
			if (M.value.length === 0) {
				l.notify("warning", "Please select at least one LR receipt.");
				return;
			}
			S.value = !0;
			try {
				let e = await Ne(l.client, M.value);
				l.notify("success", `Trip #${e.trip_no} created.`), l.router?.push(A.trip(e.id));
			} catch (e) {
				l.notify("error", I(e, "Unable to create the trip."));
			} finally {
				S.value = !1;
			}
		}
		return (t, c) => {
			let l = y("BaseBreadcrumbItem"), _ = y("BaseBreadcrumb"), C = y("BaseButton"), T = y("BasePageHeader"), O = y("BaseCustomerSelectInput"), M = y("BaseInputGroup"), oe = y("BaseIcon"), se = y("BaseMultiselect"), ce = y("BaseCard"), le = y("BasePage");
			return g(), n(le, { class: "relative" }, {
				default: w(() => [a("form", {
					class: "flex flex-col gap-4 md:gap-5",
					onSubmit: E(ae, ["prevent"])
				}, [s(T, { title: "New Trip" }, {
					actions: w(() => [s(C, {
						loading: S.value,
						disabled: S.value,
						variant: "primary",
						type: "submit"
					}, {
						default: w(() => [...c[1] ||= [o(" Create Trip ", -1)]]),
						_: 1
					}, 8, ["loading", "disabled"])]),
					default: w(() => [s(_, null, {
						default: w(() => [
							s(l, {
								title: "Home",
								to: "/admin/dashboard"
							}),
							s(l, {
								title: "Trips",
								to: x(A).board
							}, null, 8, ["to"]),
							s(l, {
								title: "New Trip",
								to: "#",
								active: ""
							})
						]),
						_: 1
					})]),
					_: 1
				}), s(ce, { "container-class": "p-4 md:p-5" }, {
					default: w(() => [
						a("div", Ge, [s(M, {
							label: "Customer",
							required: ""
						}, {
							default: w(() => [s(O, {
								modelValue: u.value,
								"onUpdate:modelValue": c[0] ||= (e) => u.value = e,
								disabled: f.value,
								placeholder: "Select a customer",
								"show-action": ""
							}, null, 8, ["modelValue", "disabled"]), a("div", Ke, [c[2] ||= a("span", null, "Select a customer to view their unlinked LR receipts.", -1), a("button", {
								type: "button",
								class: "font-medium text-primary-600 hover:text-primary-700 underline",
								onClick: D
							}, b(f.value ? "← Select specific customer" : "Or load all customers (part-load)"), 1)])]),
							_: 1
						})]),
						a("section", qe, [
							a("div", Je, [c[5] ||= a("div", null, [a("h2", { class: "text-section font-semibold text-heading" }, "LR Receipts"), a("p", { class: "mt-1 text-sm text-muted" }, " Choose the LR receipts this trip carries. ")], -1), a("div", Ye, [s(C, {
								type: "button",
								size: "sm",
								variant: "primary-outline",
								disabled: h.value || p.value.length === 0,
								onClick: ie
							}, {
								default: w(() => [...c[3] ||= [o(" Add Oldest First ", -1)]]),
								_: 1
							}, 8, ["disabled"]), s(C, {
								type: "button",
								size: "sm",
								variant: "primary-outline",
								disabled: h.value || !u.value && !f.value || j(null).length === 0,
								onClick: ne
							}, {
								left: w((e) => [s(oe, {
									name: "PlusIcon",
									class: d(e.class)
								}, null, 8, ["class"])]),
								default: w(() => [c[4] ||= o(" Add LR Receipt ", -1)]),
								_: 1
							}, 8, ["disabled"])])]),
							ee.value.length > 1 ? (g(), i("div", Xe, [a("span", Ze, "📦 Consolidated Part-Load (" + b(ee.value.length) + " Customers):", 1), a("span", null, b(ee.value.join(", ")), 1)])) : ee.value.length === 1 ? (g(), i("div", Qe, [a("span", null, [c[6] ||= o("👤 Customer: ", -1), a("strong", $e, b(ee.value[0]), 1)])])) : r("", !0),
							!u.value && !f.value ? (g(), i("div", et, " Please select a customer above to view and add LR receipts. ")) : h.value && p.value.length === 0 ? (g(), i("div", tt, " Loading LR receipts… ")) : p.value.length === 0 ? (g(), i("div", nt, " No unlinked LR receipts found for this customer. ")) : m.value.length === 0 ? (g(), i("div", rt, " Click \"+ Add LR Receipt\" or \"Add Oldest First\" to add LR receipts to this trip. ")) : (g(), i("div", it, [(g(!0), i(e, null, v(m.value, (e, t) => (g(), i("div", {
								key: t,
								class: "grid items-end gap-3 rounded-lg bg-surface-secondary p-3 md:grid-cols-[minmax(0,1fr)_12rem_auto]"
							}, [
								s(M, { label: "LR Receipt" }, {
									default: w(() => [s(se, {
										modelValue: e.receipt_id,
										"onUpdate:modelValue": (t) => e.receipt_id = t,
										"value-prop": "id",
										"track-by": "id",
										label: "label",
										options: j(e.receipt_id),
										loading: h.value,
										placeholder: "Select an LR",
										searchable: ""
									}, null, 8, [
										"modelValue",
										"onUpdate:modelValue",
										"options",
										"loading"
									])]),
									_: 2
								}, 1024),
								s(M, { label: "Amount" }, {
									default: w(() => [a("div", at, b(e.receipt_id ? x(L)(k(e.receipt_id)) : "—"), 1)]),
									_: 2
								}, 1024),
								a("div", ot, [s(C, {
									type: "button",
									size: "sm",
									variant: "gray",
									"aria-label": "Remove LR receipt",
									onClick: (e) => re(t)
								}, {
									default: w(() => [s(oe, { name: "TrashIcon" })]),
									_: 1
								}, 8, ["onClick"])])
							]))), 128))])),
							te.value ? (g(), i("p", st, " ⚠ Different routes selected — same vehicle? ")) : r("", !0),
							a("div", ct, [a("span", lt, [c[7] ||= o(" Receipts: ", -1), a("span", ut, b(N.value.length), 1)]), a("span", dt, [c[8] ||= o(" Revenue: ", -1), a("span", ft, b(x(L)(P.value)), 1)])])
						]),
						a("section", pt, [
							c[11] ||= a("h2", { class: "mb-4 text-section font-semibold text-heading" }, "Summary", -1),
							a("div", mt, [a("div", ht, [c[9] ||= a("p", { class: "text-xs text-muted" }, "Selected LR Receipts", -1), a("p", gt, b(N.value.length), 1)]), a("div", _t, [c[10] ||= a("p", { class: "text-xs text-muted" }, "Total Freight Revenue", -1), a("p", vt, b(x(L)(P.value)), 1)])]),
							c[12] ||= a("p", { class: "mt-3 text-center text-xs text-muted" }, " Lorry Receipt and expenses are fetched & linked on the trip details page to calculate vehicle cost and net profit. ", -1)
						])
					]),
					_: 1
				})], 32)]),
				_: 1
			});
		};
	}
}), bt = { class: "space-y-3" }, xt = {
	key: 1,
	class: "flex items-center justify-between text-xs text-muted"
}, St = {
	key: 2,
	class: "flex items-center justify-between text-xs text-muted"
}, Ct = {
	key: 3,
	class: "py-8 text-center text-sm text-muted"
}, wt = {
	key: 4,
	class: "rounded-lg bg-surface-secondary p-4 text-sm text-muted"
}, Tt = { key: 0 }, Et = { key: 1 }, Dt = { key: 2 }, Ot = {
	key: 5,
	class: "max-h-72 space-y-1 overflow-y-auto"
}, kt = ["onClick"], At = ["checked", "onChange"], jt = { class: "min-w-0 flex-1" }, Mt = { class: "block font-medium text-heading" }, Nt = { class: "block truncate text-xs text-muted" }, Pt = { class: "text-sm font-semibold text-heading" }, Ft = {
	key: 6,
	class: "text-sm text-muted"
}, It = {
	key: 7,
	class: "text-sm text-alert-warning-text"
}, Lt = { class: "flex justify-end gap-2" }, Rt = /* @__PURE__ */ c({
	__name: "LrPickerModal",
	props: {
		show: { type: Boolean },
		client: { type: [Function, Object] },
		notify: { type: Function },
		type: {},
		multiple: { type: Boolean },
		customers: {},
		tripId: {}
	},
	emits: ["close", "select"],
	setup(c, { emit: l }) {
		let u = c, f = l, p = _([]), h = _([]), S = _(null), T = _(!1), D = _(!1), O = t(() => u.multiple ?? !0);
		async function k() {
			D.value = !0;
			try {
				p.value = await Le(u.client, u.type ?? "lr", S.value, u.tripId ?? null, T.value), h.value = [];
			} catch (e) {
				u.notify("error", I(e, "Unable to load the receipts."));
			} finally {
				D.value = !1;
			}
		}
		m(() => {
			u.show && k();
		}), C(() => u.show, (e) => {
			e && (T.value = !1, k());
		}), C([S, T], () => {
			k();
		});
		function A(e) {
			if (!O.value) {
				h.value = [e];
				return;
			}
			h.value.includes(e) ? h.value = h.value.filter((t) => t !== e) : h.value = [...h.value, e];
		}
		let j = t(() => p.value.filter((e) => h.value.includes(e.id)).reduce((e, t) => e + t.amount, 0)), M = t(() => {
			let e = p.value.filter((e) => h.value.includes(e.id));
			return new Set(e.map((e) => `${e.from_city}→${e.to_city}`)).size > 1;
		});
		function N() {
			h.value.length !== 0 && f("select", [...h.value]);
		}
		return (t, l) => {
			let u = y("BaseSelectInput"), m = y("BaseInputGroup"), _ = y("BaseButton"), C = y("BaseModal");
			return g(), n(C, {
				show: c.show,
				onClose: l[6] ||= (e) => f("close")
			}, {
				header: w(() => [a("span", null, b(c.type === "lorry" ? "Map Lorry Receipt" : "Map LR Receipts"), 1)]),
				default: w(() => [a("div", bt, [
					c.customers && c.customers.length > 0 ? (g(), n(m, {
						key: 0,
						label: "Customer"
					}, {
						default: w(() => [s(u, {
							modelValue: S.value,
							"onUpdate:modelValue": l[0] ||= (e) => S.value = e,
							options: [{
								id: null,
								label: "All customers"
							}, ...c.customers.map((e) => ({
								id: e.id,
								label: e.name
							}))],
							"value-prop": "id",
							"label-key": "label"
						}, null, 8, ["modelValue", "options"])]),
						_: 1
					})) : r("", !0),
					c.type === "lorry" && c.tripId ? (g(), i("div", xt, [a("span", null, b(T.value ? "Showing all unlinked lorry receipts" : "Showing lorry receipts matching this trip’s LR numbers"), 1), a("button", {
						type: "button",
						class: "font-medium text-primary-600 hover:underline",
						onClick: l[1] ||= (e) => T.value = !T.value
					}, b(T.value ? "Show matching only" : "Show all unlinked"), 1)])) : c.type === "lr" && c.tripId ? (g(), i("div", St, [a("span", null, b(T.value ? "Showing all unlinked LR receipts" : "Showing unlinked LR receipts for customers on this trip"), 1), a("button", {
						type: "button",
						class: "font-medium text-primary-600 hover:underline",
						onClick: l[2] ||= (e) => T.value = !T.value
					}, b(T.value ? "Show trip customers only" : "Browse all customers"), 1)])) : r("", !0),
					D.value ? (g(), i("div", Ct, "Loading…")) : p.value.length === 0 ? (g(), i("div", wt, [c.type === "lorry" && c.tripId && !T.value ? (g(), i("p", Tt, [l[7] ||= o(" No lorry receipt found with matching bilty numbers for this trip's LRs. ", -1), a("button", {
						type: "button",
						class: "mt-1 block font-medium text-primary-600 hover:underline",
						onClick: l[3] ||= (e) => T.value = !0
					}, " Browse all unlinked lorry receipts instead → ")])) : c.type === "lr" && c.tripId && !T.value ? (g(), i("p", Et, [l[8] ||= o(" No more unassigned LR receipts found for the customers on this trip. ", -1), a("button", {
						type: "button",
						class: "mt-1 block font-medium text-primary-600 hover:underline",
						onClick: l[4] ||= (e) => T.value = !0
					}, " Browse all unlinked LR receipts instead → ")])) : (g(), i("p", Dt, " No unlinked receipts. Create one first. "))])) : (g(), i("ul", Ot, [(g(!0), i(e, null, v(p.value, (e) => (g(), i("li", { key: e.id }, [a("label", {
						class: d(["flex cursor-pointer items-center gap-3 rounded-lg border border-line-default bg-surface p-2.5 text-sm hover:bg-hover", { "border-primary-500": h.value.includes(e.id) }]),
						onClick: E((t) => A(e.id), ["prevent"])
					}, [
						a("input", {
							type: "checkbox",
							class: "accent-primary-600",
							checked: h.value.includes(e.id),
							onChange: (t) => A(e.id)
						}, null, 40, At),
						a("span", jt, [a("span", Mt, b(e.invoice_number) + " · " + b(e.from_city ?? "—") + " → " + b(e.to_city ?? "—"), 1), a("span", Nt, b(e.customer_name ?? "No customer") + " · " + b(e.goods ?? ""), 1)]),
						a("span", Pt, b(x(L)(e.amount)), 1)
					], 10, kt)]))), 128))])),
					h.value.length > 0 ? (g(), i("p", Ft, " Selected: " + b(h.value.length) + " · " + b(x(L)(j.value)), 1)) : r("", !0),
					M.value ? (g(), i("p", It, " ⚠ Different routes selected — same vehicle? ")) : r("", !0)
				])]),
				footer: w(() => [a("div", Lt, [s(_, {
					variant: "primary-outline",
					size: "sm",
					onClick: l[5] ||= (e) => f("close")
				}, {
					default: w(() => [...l[9] ||= [o("Cancel", -1)]]),
					_: 1
				}), s(_, {
					variant: "primary",
					size: "sm",
					disabled: h.value.length === 0,
					onClick: N
				}, {
					default: w(() => [o(b(O.value ? `Link ${h.value.length}` : "Link"), 1)]),
					_: 1
				}, 8, ["disabled"])])]),
				_: 1
			}, 8, ["show"]);
		};
	}
}), zt = { class: "min-h-screen bg-surface-secondary" }, Bt = { class: "mx-auto max-w-5xl p-4 lg:p-6" }, Vt = {
	key: 0,
	class: "py-16 text-center text-sm text-muted"
}, Ht = { class: "mb-4 flex flex-wrap items-center justify-between gap-3" }, Ut = { class: "text-xl font-semibold text-heading" }, Wt = { class: "flex items-center gap-2" }, Gt = {
	key: 0,
	class: "rounded-full bg-red-600 px-3 py-1 text-xs font-semibold text-white"
}, Kt = ["disabled"], qt = ["value"], Jt = {
	key: 0,
	class: "mb-4 rounded-lg bg-alert-error-bg p-3 text-sm text-alert-error-text"
}, Yt = { class: "space-y-4" }, Xt = { class: "space-y-2" }, Zt = { class: "min-w-0" }, Qt = { class: "font-medium text-heading" }, $t = { class: "truncate text-xs text-muted" }, en = { class: "flex shrink-0 gap-1" }, tn = {
	key: 0,
	class: "text-sm text-muted"
}, nn = { class: "flex gap-2 pt-1" }, rn = { class: "mt-4 border-t border-line-light pt-3" }, an = { class: "mb-2 flex items-center justify-between" }, on = { class: "flex items-center gap-2" }, sn = { class: "min-w-0" }, cn = { class: "font-medium text-heading" }, ln = { class: "truncate text-xs text-muted" }, un = { class: "flex shrink-0 gap-1" }, dn = {
	key: 0,
	class: "text-sm text-muted"
}, fn = {
	key: 0,
	class: "text-xs text-muted"
}, pn = { class: "mt-4 border-t border-line-light pt-3" }, mn = { class: "mb-2 flex items-center justify-between" }, hn = { class: "flex items-center gap-2" }, gn = { class: "min-w-0" }, _n = { class: "font-medium text-heading" }, vn = { class: "truncate text-xs text-muted" }, yn = { class: "flex shrink-0 gap-1" }, bn = {
	key: 0,
	class: "text-sm text-muted"
}, xn = {
	key: 0,
	class: "text-xs text-muted"
}, Sn = { class: "mt-4 border-t border-line-light pt-3" }, Cn = { class: "mb-2 text-sm font-semibold text-heading" }, wn = { class: "grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" }, Tn = { class: "mb-1.5 text-xs font-medium text-muted" }, En = {
	key: 0,
	class: "group relative flex h-28 items-center justify-center overflow-hidden rounded-lg border border-line-default bg-surface-secondary"
}, Dn = ["src", "alt"], On = {
	key: 1,
	class: "flex flex-col items-center gap-1 text-subtle"
}, kn = {
	key: 2,
	class: "flex items-center justify-center text-subtle"
}, An = { class: "absolute inset-0 flex items-center justify-center gap-1 bg-black/40 opacity-0 transition-opacity group-hover:opacity-100" }, jn = ["onClick"], Mn = ["disabled", "onClick"], Nn = {
	key: 2,
	class: "mt-1 flex items-center justify-between gap-1"
}, Pn = { class: "min-w-0 truncate text-xs text-muted" }, Fn = ["onClick"], In = { class: "mt-4 border-t border-line-light pt-3" }, Ln = { class: "grid grid-cols-3 gap-3 text-center" }, Rn = { class: "rounded-lg bg-surface-secondary p-3" }, zn = { class: "text-lg font-semibold text-heading" }, Bn = { class: "rounded-lg bg-surface-secondary p-3" }, Vn = { class: "text-lg font-semibold text-heading" }, Hn = { class: "rounded-lg bg-surface-secondary p-3" }, Un = {
	key: 0,
	class: "mt-3"
}, Wn = { class: "flex items-center justify-between gap-2" }, Gn = { class: "min-w-0" }, Kn = { class: "font-medium text-heading" }, qn = { class: "truncate text-xs text-muted" }, Jn = {
	key: 0,
	class: "mt-2 space-y-1"
}, Yn = { class: "text-muted" }, Xn = { class: "font-medium text-heading" }, Zn = {
	key: 1,
	class: "mt-3"
}, Qn = { class: "flex items-center justify-between gap-2" }, $n = { class: "min-w-0" }, er = { class: "font-medium text-heading" }, tr = { class: "truncate text-xs text-muted" }, nr = { key: 0 }, rr = {
	key: 0,
	class: "mt-1 text-xs text-muted"
}, ir = {
	key: 1,
	class: "mt-2 space-y-1"
}, ar = { class: "text-muted" }, or = { key: 0 }, sr = { class: "font-medium text-heading" }, cr = { class: "mt-3 flex items-center justify-between rounded-lg border border-line-light p-2.5 text-sm" }, lr = { class: "text-muted" }, ur = { class: "font-semibold text-heading" }, dr = ["title"], fr = /* @__PURE__ */ c({
	__name: "TripDetailPage",
	props: {
		id: {},
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {}
	},
	setup(c) {
		let l = c, u = _(null), p = _(!0), E = _([]), D = _(!1), O = _(!1), k = _(!1), j = _(!1), M = _(!1), N = _(null), ee = _(null), P = _(""), te = t(() => Number(l.id)), ne = t({
			get: () => u.value?.status.id ?? null,
			set: (e) => {
				e !== null && me(e);
			}
		}), re = t(() => u.value?.receipts.filter((e) => e.type === "lr") ?? []), ie = t(() => u.value?.receipts.filter((e) => e.type === "lorry") ?? []), ae = t(() => u.value?.receipts.filter((e) => e.type === "invoice") ?? []), oe = [
			{
				role: "driver",
				label: "Driver Documents",
				slots: [
					{
						key: "driver_aadhar",
						label: "Aadhar Card"
					},
					{
						key: "driver_license",
						label: "Driving Licence"
					},
					{
						key: "driver_pan",
						label: "PAN Card"
					}
				]
			},
			{
				role: "owner",
				label: "Owner Documents",
				slots: [
					{
						key: "owner_aadhar",
						label: "Aadhar Card"
					},
					{
						key: "owner_pan",
						label: "PAN Card"
					},
					{
						key: "owner_rc",
						label: "Vehicle RC"
					}
				]
			},
			{
				role: "broker",
				label: "Broker Documents",
				slots: [{
					key: "broker_aadhar",
					label: "Aadhar Card"
				}, {
					key: "broker_pan",
					label: "PAN Card"
				}]
			}
		];
		function se(e) {
			return u.value?.documents.find((t) => t.category === e) ?? null;
		}
		let ce = _({});
		function le() {
			for (let e of Object.values(ce.value)) URL.revokeObjectURL(e);
			ce.value = {};
		}
		async function ue() {
			if (u.value) {
				le();
				for (let e of u.value.documents) try {
					let t = await Ue(l.client, u.value.id, e.id);
					ce.value[e.id] = URL.createObjectURL(t);
				} catch {}
			}
		}
		function de(e) {
			let t = ce.value[e];
			t && window.open(t, "_blank", "noopener");
		}
		C(() => u.value?.documents, () => {
			ue();
		});
		async function fe() {
			p.value = !0;
			try {
				u.value = await Me(l.client, te.value);
			} catch (e) {
				l.notify("error", I(e, "Unable to load the trip."));
			} finally {
				p.value = !1;
			}
		}
		async function pe() {
			try {
				E.value = await je(l.client);
			} catch {}
		}
		async function me(e) {
			if (!(!u.value || D.value)) {
				D.value = !0;
				try {
					u.value = await Ie(l.client, u.value.id, e), l.notify("success", "Status updated.");
				} catch (e) {
					l.notify("error", I(e, "Unable to change the status."));
				} finally {
					D.value = !1;
				}
			}
		}
		function he() {
			document.hidden || fe();
		}
		m(() => {
			fe(), pe(), document.addEventListener("visibilitychange", he);
		}), h(() => {
			le(), document.removeEventListener("visibilitychange", he);
		});
		async function ge() {
			if (!(!u.value || !window.confirm("Cancel this trip?"))) try {
				u.value = await Fe(l.client, u.value.id), l.notify("success", "Trip cancelled.");
			} catch (e) {
				l.notify("error", I(e, "Unable to cancel the trip."));
			}
		}
		async function _e(e) {
			if (u.value) {
				O.value = !1;
				try {
					for (let t of e) u.value = await Re(l.client, u.value.id, t, "lr");
					l.notify("success", "LR receipt linked.");
				} catch (e) {
					l.notify("error", I(e, "Unable to link the receipt."));
				}
			}
		}
		async function ve(e) {
			if (!(!u.value || e.length === 0)) {
				k.value = !1;
				try {
					for (let t of e) u.value = await Re(l.client, u.value.id, t, "lorry");
					l.notify("success", "Lorry receipt linked.");
				} catch (e) {
					l.notify("error", I(e, "Unable to link the receipt."));
				}
			}
		}
		async function ye() {
			if (u.value) {
				M.value = !0;
				try {
					u.value = await ze(l.client, u.value.id), l.notify("success", "Lorry receipt(s) fetched and linked.");
				} catch (e) {
					l.notify("error", I(e, "No lorry receipt found with matching bilty numbers for these LRs."));
				} finally {
					M.value = !1;
				}
			}
		}
		async function be(e) {
			if (u.value) try {
				u.value = await We(l.client, u.value.id, e), l.notify("success", "Receipt unlinked.");
			} catch (e) {
				l.notify("error", I(e, "Unable to unlink the receipt."));
			}
		}
		async function xe() {
			if (u.value) {
				j.value = !0;
				try {
					u.value = await Be(l.client, u.value.id), l.notify("success", "Invoice receipt fetched and linked.");
				} catch (e) {
					l.notify("error", I(e, "No invoice receipt found for these LR numbers."));
				} finally {
					j.value = !1;
				}
			}
		}
		function Se(e) {
			P.value = e, ee.value?.click();
		}
		async function Ce(e) {
			if (!u.value) return;
			let t = e.target, n = t.files;
			if (!n || n.length === 0) return;
			let r = P.value;
			N.value = r;
			try {
				u.value = await Ve(l.client, u.value.id, n[0], r), l.notify("success", "Document uploaded.");
			} catch (e) {
				l.notify("error", I(e, "Unable to upload the document."));
			} finally {
				N.value = null, t.value = "";
			}
		}
		async function we(e) {
			if (u.value) try {
				u.value = await He(l.client, u.value.id, e), l.notify("success", "Document removed.");
			} catch (e) {
				l.notify("error", I(e, "Unable to remove the document."));
			}
		}
		function Te(e) {
			l.router?.push(A.invoiceView(e));
		}
		function Ee() {
			l.router?.push(A.board);
		}
		return (t, l) => {
			let m = y("BaseButton"), h = y("BaseSettingCard");
			return g(), i("div", zt, [
				a("div", Bt, [p.value ? (g(), i("div", Vt, "Loading the trip…")) : u.value ? (g(), i(e, { key: 1 }, [
					a("div", Ht, [a("div", null, [a("button", {
						type: "button",
						class: "text-sm text-muted hover:text-heading",
						onClick: Ee
					}, " ← Trips "), a("h1", Ut, " Trip #" + b(u.value.trip_no) + " · " + b(u.value.from_city ?? "—") + " → " + b(u.value.to_city ?? "—"), 1)]), a("div", Wt, [u.value.cancelled_at ? (g(), i("span", Gt, " Cancelled ")) : E.value.length > 0 ? T((g(), i("select", {
						key: 1,
						"onUpdate:modelValue": l[0] ||= (e) => ne.value = e,
						disabled: D.value,
						class: "cursor-pointer rounded-full border-0 px-3 py-1 text-xs font-semibold text-white outline-none",
						style: f({ backgroundColor: u.value.status.colour ?? "#94a3b8" })
					}, [(g(!0), i(e, null, v(E.value, (e) => (g(), i("option", {
						key: e.id,
						value: e.id,
						class: "bg-surface text-body"
					}, b(e.name), 9, qt))), 128))], 12, Kt)), [[S, ne.value]]) : (g(), i("span", {
						key: 2,
						class: "rounded-full px-3 py-1 text-xs font-semibold text-white",
						style: f({ backgroundColor: u.value.status.colour ?? "#94a3b8" })
					}, b(u.value.status.name), 5)), u.value.cancelled_at ? r("", !0) : (g(), n(m, {
						key: 3,
						size: "sm",
						variant: "danger",
						onClick: ge
					}, {
						default: w(() => [...l[5] ||= [o(" Cancel Trip ", -1)]]),
						_: 1
					}))])]),
					u.value.cancelled_at ? (g(), i("div", Jt, " This trip is cancelled. ")) : r("", !0),
					a("div", Yt, [s(h, {
						title: "Documents",
						description: "LR receipts, lorry receipt, invoice receipt and trip documents for this trip."
					}, {
						default: w(() => [
							a("div", Xt, [
								(g(!0), i(e, null, v(re.value, (e) => (g(), i("div", {
									key: e.id,
									class: "flex items-center justify-between gap-2 rounded-lg border border-line-light p-2.5 text-sm"
								}, [a("div", Zt, [a("p", Qt, "📄 " + b(e.invoice_number), 1), a("p", $t, b(e.customer_name ?? "No customer") + " · " + b(x(L)(e.amount)), 1)]), a("div", en, [s(m, {
									size: "sm",
									variant: "primary-outline",
									onClick: (t) => Te(e.invoice_id)
								}, {
									default: w(() => [...l[6] ||= [o(" View ", -1)]]),
									_: 1
								}, 8, ["onClick"]), e.billed_invoice_id ? r("", !0) : (g(), n(m, {
									key: 0,
									size: "sm",
									variant: "danger-outline",
									onClick: (t) => be(e.id)
								}, {
									default: w(() => [...l[7] ||= [o(" Unlink ", -1)]]),
									_: 1
								}, 8, ["onClick"]))])]))), 128)),
								re.value.length === 0 ? (g(), i("p", tn, "No LR receipts linked yet.")) : r("", !0),
								a("div", nn, [s(m, {
									size: "sm",
									variant: "primary-outline",
									onClick: l[1] ||= (e) => O.value = !0
								}, {
									default: w(() => [...l[8] ||= [o(" + Link LR ", -1)]]),
									_: 1
								})])
							]),
							a("div", rn, [
								a("div", an, [l[10] ||= a("p", { class: "text-xs font-semibold uppercase tracking-wide text-muted" }, "Lorry Receipt", -1), a("div", on, [s(m, {
									size: "sm",
									variant: "primary-outline",
									disabled: M.value || re.value.length === 0,
									onClick: ye
								}, {
									default: w(() => [o(b(M.value ? "Fetching…" : "Fetch Lorry Receipt"), 1)]),
									_: 1
								}, 8, ["disabled"]), s(m, {
									size: "sm",
									variant: "primary-outline",
									onClick: l[2] ||= (e) => k.value = !0
								}, {
									default: w(() => [...l[9] ||= [o(" + Link Lorry Receipt ", -1)]]),
									_: 1
								})])]),
								(g(!0), i(e, null, v(ie.value, (e) => (g(), i("div", {
									key: e.id,
									class: "mb-2 flex items-center justify-between gap-2 rounded-lg border border-line-light p-2.5 text-sm"
								}, [a("div", sn, [a("p", cn, "🚛 " + b(e.invoice_number), 1), a("p", ln, b(e.customer_name ?? "No customer") + " · " + b(x(L)(e.amount)), 1)]), a("div", un, [s(m, {
									size: "sm",
									variant: "primary-outline",
									onClick: (t) => Te(e.invoice_id)
								}, {
									default: w(() => [...l[11] ||= [o(" View ", -1)]]),
									_: 1
								}, 8, ["onClick"]), e.billed_invoice_id ? r("", !0) : (g(), n(m, {
									key: 0,
									size: "sm",
									variant: "danger-outline",
									onClick: (t) => be(e.id)
								}, {
									default: w(() => [...l[12] ||= [o(" Unlink ", -1)]]),
									_: 1
								}, 8, ["onClick"]))])]))), 128)),
								ie.value.length === 0 ? (g(), i("p", dn, [l[13] ||= o(" No lorry receipt linked yet. ", -1), re.value.length === 0 ? (g(), i("span", fn, " (Link an LR first)")) : r("", !0)])) : r("", !0)
							]),
							a("div", pn, [
								a("div", mn, [l[14] ||= a("p", { class: "text-xs font-semibold uppercase tracking-wide text-muted" }, "Invoice Receipt", -1), a("div", hn, [s(m, {
									size: "sm",
									variant: "primary-outline",
									disabled: j.value || re.value.length === 0,
									onClick: xe
								}, {
									default: w(() => [o(b(j.value ? "Fetching…" : "Fetch Invoice Receipt"), 1)]),
									_: 1
								}, 8, ["disabled"])])]),
								(g(!0), i(e, null, v(ae.value, (e) => (g(), i("div", {
									key: e.id,
									class: "mb-2 flex items-center justify-between gap-2 rounded-lg border border-line-light p-2.5 text-sm"
								}, [a("div", gn, [a("p", _n, "🧾 " + b(e.invoice_number), 1), a("p", vn, b(e.customer_name ?? "No customer") + " · " + b(x(L)(e.amount)), 1)]), a("div", yn, [s(m, {
									size: "sm",
									variant: "primary-outline",
									onClick: (t) => Te(e.invoice_id)
								}, {
									default: w(() => [...l[15] ||= [o(" View ", -1)]]),
									_: 1
								}, 8, ["onClick"]), e.billed_invoice_id ? r("", !0) : (g(), n(m, {
									key: 0,
									size: "sm",
									variant: "danger-outline",
									onClick: (t) => be(e.id)
								}, {
									default: w(() => [...l[16] ||= [o(" Unlink ", -1)]]),
									_: 1
								}, 8, ["onClick"]))])]))), 128)),
								ae.value.length === 0 ? (g(), i("p", bn, [l[17] ||= o(" No invoice receipt linked yet. ", -1), re.value.length === 0 ? (g(), i("span", xn, " (Link an LR first)")) : r("", !0)])) : r("", !0)
							]),
							a("div", Sn, [
								l[26] ||= a("p", { class: "mb-3 text-xs font-semibold uppercase tracking-wide text-muted" }, "Trip Documents", -1),
								a("input", {
									ref_key: "podFileInput",
									ref: ee,
									type: "file",
									class: "hidden",
									accept: "image/*,application/pdf",
									onChange: Ce
								}, null, 544),
								(g(), i(e, null, v(oe, (t) => a("div", {
									key: t.role,
									class: "mb-4 last:mb-0"
								}, [a("p", Cn, b(t.label), 1), a("div", wn, [(g(!0), i(e, null, v(t.slots, (t) => (g(), i("div", {
									key: t.key,
									class: "flex flex-col"
								}, [
									a("p", Tn, b(t.label), 1),
									se(t.key) ? (g(), i("div", En, [se(t.key).is_image && ce.value[se(t.key).id] ? (g(), i("img", {
										key: 0,
										src: ce.value[se(t.key).id],
										alt: t.label,
										class: "h-full w-full object-cover"
									}, null, 8, Dn)) : se(t.key).is_image ? (g(), i("div", kn, [...l[19] ||= [a("svg", {
										class: "h-5 w-5 animate-spin",
										fill: "none",
										viewBox: "0 0 24 24"
									}, [a("circle", {
										class: "opacity-25",
										cx: "12",
										cy: "12",
										r: "10",
										stroke: "currentColor",
										"stroke-width": "4"
									}), a("path", {
										class: "opacity-75",
										fill: "currentColor",
										d: "M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
									})], -1)]])) : (g(), i("div", On, [...l[18] ||= [a("svg", {
										class: "h-8 w-8",
										fill: "none",
										viewBox: "0 0 24 24",
										stroke: "currentColor"
									}, [a("path", {
										"stroke-linecap": "round",
										"stroke-linejoin": "round",
										"stroke-width": "1.25",
										d: "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
									})], -1), a("span", { class: "text-xs" }, "PDF", -1)]])), a("div", An, [s(m, {
										size: "sm",
										variant: "primary",
										onClick: (e) => de(se(t.key).id)
									}, {
										default: w(() => [...l[20] ||= [o(" View ", -1)]]),
										_: 1
									}, 8, ["onClick"]), a("button", {
										type: "button",
										class: "flex h-7 w-7 items-center justify-center rounded-full bg-surface text-status-red shadow",
										"aria-label": "Remove",
										onClick: (e) => we(se(t.key).id)
									}, [...l[21] ||= [a("svg", {
										class: "h-4 w-4",
										fill: "none",
										viewBox: "0 0 24 24",
										stroke: "currentColor"
									}, [a("path", {
										"stroke-linecap": "round",
										"stroke-linejoin": "round",
										"stroke-width": "2",
										d: "M6 18L18 6M6 6l12 12"
									})], -1)]], 8, jn)])])) : (g(), i("button", {
										key: 1,
										type: "button",
										class: "flex h-28 flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-line-strong bg-surface-secondary text-subtle transition-colors hover:border-primary-400 hover:text-primary-500",
										disabled: N.value === t.key,
										onClick: (e) => Se(t.key)
									}, [N.value === t.key ? (g(), i(e, { key: 0 }, [l[22] ||= a("svg", {
										class: "h-5 w-5 animate-spin",
										fill: "none",
										viewBox: "0 0 24 24"
									}, [a("circle", {
										class: "opacity-25",
										cx: "12",
										cy: "12",
										r: "10",
										stroke: "currentColor",
										"stroke-width": "4"
									}), a("path", {
										class: "opacity-75",
										fill: "currentColor",
										d: "M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
									})], -1), l[23] ||= a("span", { class: "text-xs" }, "Uploading…", -1)], 64)) : (g(), i(e, { key: 1 }, [l[24] ||= a("svg", {
										class: "h-6 w-6",
										fill: "none",
										viewBox: "0 0 24 24",
										stroke: "currentColor"
									}, [a("path", {
										"stroke-linecap": "round",
										"stroke-linejoin": "round",
										"stroke-width": "1.5",
										d: "M12 4v16m8-8H4"
									})], -1), l[25] ||= a("span", { class: "text-xs" }, "Upload", -1)], 64))], 8, Mn)),
									se(t.key) ? (g(), i("div", Nn, [a("span", Pn, b(se(t.key).file_name), 1), a("button", {
										type: "button",
										class: "shrink-0 text-xs text-primary-500 hover:underline",
										onClick: (e) => Se(t.key)
									}, " Replace ", 8, Fn)])) : r("", !0)
								]))), 128))])])), 64))
							]),
							a("div", In, [
								l[32] ||= a("p", { class: "mb-2 text-xs font-semibold uppercase tracking-wide text-muted" }, "The Money", -1),
								a("div", Ln, [
									a("div", Rn, [l[27] ||= a("p", { class: "text-xs text-muted" }, "Revenue", -1), a("p", zn, b(x(L)(u.value.money.revenue)), 1)]),
									a("div", Bn, [l[28] ||= a("p", { class: "text-xs text-muted" }, "Cost", -1), a("p", Vn, b(x(L)(u.value.money.cost)), 1)]),
									a("div", Hn, [l[29] ||= a("p", { class: "text-xs text-muted" }, "Profit", -1), a("p", { class: d(["text-lg font-semibold", u.value.money.profit >= 0 ? "text-status-green" : "text-status-red"]) }, b(x(L)(u.value.money.profit)), 3)])
								]),
								u.value.money.receivable.length > 0 ? (g(), i("div", Un, [l[30] ||= a("p", { class: "mb-2 text-xs font-semibold uppercase tracking-wide text-muted" }, "From Customer", -1), (g(!0), i(e, null, v(u.value.money.receivable, (t) => (g(), i("div", {
									key: t.invoice_id,
									class: "mb-2 rounded-lg border border-line-light p-2.5 text-sm"
								}, [a("div", Wn, [a("div", Gn, [a("p", Kn, "🧾 " + b(t.invoice_number), 1), a("p", qn, b(t.customer_name ?? "No customer") + " · Total " + b(x(L)(t.total)) + " · Due " + b(x(L)(t.due_amount)), 1)]), a("span", { class: d(["shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold", {
									"bg-status-red/10 text-status-red": t.paid_status === "UNPAID",
									"bg-status-yellow/10 text-status-yellow": t.paid_status === "PARTIALLY_PAID",
									"bg-status-green/10 text-status-green": t.paid_status === "PAID"
								}]) }, b(t.paid_status), 3)]), t.payments.length > 0 ? (g(), i("div", Jn, [(g(!0), i(e, null, v(t.payments, (e) => (g(), i("div", {
									key: e.id,
									class: "flex items-center justify-between gap-2 rounded bg-surface-secondary px-2 py-1 text-xs"
								}, [a("span", Yn, b(e.number ?? "—") + " · " + b(e.date ?? "—"), 1), a("span", Xn, b(x(L)(e.amount)), 1)]))), 128))])) : r("", !0)]))), 128))])) : r("", !0),
								u.value.money.payable.length > 0 ? (g(), i("div", Zn, [l[31] ||= a("p", { class: "mb-2 text-xs font-semibold uppercase tracking-wide text-muted" }, "To Owner", -1), (g(!0), i(e, null, v(u.value.money.payable, (t) => (g(), i("div", {
									key: t.invoice_number,
									class: "mb-2 rounded-lg border border-line-light p-2.5 text-sm"
								}, [
									a("div", Qn, [a("div", $n, [a("p", er, "🚛 " + b(t.bill_number ?? t.invoice_number), 1), a("p", tr, [o(b(t.supplier_name ?? "Unknown supplier"), 1), t.bill_reference ? (g(), i("span", nr, " · " + b(t.bill_reference), 1)) : r("", !0)])]), t.bill_status ? (g(), i("span", {
										key: 0,
										class: d(["shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold", {
											"bg-status-red/10 text-status-red": t.bill_status === "UNPAID",
											"bg-status-yellow/10 text-status-yellow": t.bill_status === "PARTIAL",
											"bg-status-green/10 text-status-green": t.bill_status === "SETTLED"
										}])
									}, b(t.bill_status), 3)) : r("", !0)]),
									t.bill_total === null ? r("", !0) : (g(), i("p", rr, " Total " + b(x(L)(t.bill_total)) + " · Due " + b(x(L)(t.bill_due ?? 0)), 1)),
									t.payments.length > 0 ? (g(), i("div", ir, [(g(!0), i(e, null, v(t.payments, (e) => (g(), i("div", {
										key: e.id,
										class: "flex items-center justify-between gap-2 rounded bg-surface-secondary px-2 py-1 text-xs"
									}, [a("span", ar, [o(b(e.type === "advance" ? "Advance" : "Final") + " · " + b(e.date ?? "—"), 1), e.reference ? (g(), i("span", or, " · " + b(e.reference), 1)) : r("", !0)]), a("span", sr, b(x(L)(e.amount)), 1)]))), 128))])) : r("", !0)
								]))), 128))])) : r("", !0),
								a("div", cr, [a("span", lr, [o(" Company paid " + b(x(L)(u.value.money.owner_paid)) + " · balance ", 1), a("span", ur, b(x(L)(u.value.money.owner_balance)), 1)]), u.value.money.bill_status ? (g(), i("span", {
									key: 0,
									class: d(["rounded-full px-2 py-0.5 text-xs font-semibold", {
										"bg-status-red/10 text-status-red": u.value.money.bill_status === "UNPAID",
										"bg-status-yellow/10 text-status-yellow": u.value.money.bill_status === "PARTIAL",
										"bg-status-green/10 text-status-green": u.value.money.bill_status === "SETTLED"
									}]),
									title: `Bill ${u.value.money.bill_number}`
								}, b(u.value.money.bill_status), 11, dr)) : r("", !0)])
							])
						]),
						_: 1
					})])
				], 64)) : r("", !0)]),
				s(Rt, {
					show: O.value,
					client: c.client,
					notify: c.notify,
					type: "lr",
					multiple: "",
					"trip-id": u.value?.id,
					onClose: l[3] ||= (e) => O.value = !1,
					onSelect: _e
				}, null, 8, [
					"show",
					"client",
					"notify",
					"trip-id"
				]),
				s(Rt, {
					show: k.value,
					client: c.client,
					notify: c.notify,
					type: "lorry",
					multiple: "",
					"trip-id": u.value?.id,
					onClose: l[4] ||= (e) => k.value = !1,
					onSelect: ve
				}, null, 8, [
					"show",
					"client",
					"notify",
					"trip-id"
				])
			]);
		};
	}
});
//#endregion
//#region ../../node_modules/sortablejs/modular/sortable.esm.js
function pr(e, t) {
	var n = Object.keys(e);
	if (Object.getOwnPropertySymbols) {
		var r = Object.getOwnPropertySymbols(e);
		t && (r = r.filter(function(t) {
			return Object.getOwnPropertyDescriptor(e, t).enumerable;
		})), n.push.apply(n, r);
	}
	return n;
}
function mr(e) {
	for (var t = 1; t < arguments.length; t++) {
		var n = arguments[t] == null ? {} : arguments[t];
		t % 2 ? pr(Object(n), !0).forEach(function(t) {
			gr(e, t, n[t]);
		}) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(n)) : pr(Object(n)).forEach(function(t) {
			Object.defineProperty(e, t, Object.getOwnPropertyDescriptor(n, t));
		});
	}
	return e;
}
function hr(e) {
	"@babel/helpers - typeof";
	return hr = typeof Symbol == "function" && typeof Symbol.iterator == "symbol" ? function(e) {
		return typeof e;
	} : function(e) {
		return e && typeof Symbol == "function" && e.constructor === Symbol && e !== Symbol.prototype ? "symbol" : typeof e;
	}, hr(e);
}
function gr(e, t, n) {
	return t in e ? Object.defineProperty(e, t, {
		value: n,
		enumerable: !0,
		configurable: !0,
		writable: !0
	}) : e[t] = n, e;
}
function _r() {
	return _r = Object.assign || function(e) {
		for (var t = 1; t < arguments.length; t++) {
			var n = arguments[t];
			for (var r in n) Object.prototype.hasOwnProperty.call(n, r) && (e[r] = n[r]);
		}
		return e;
	}, _r.apply(this, arguments);
}
function vr(e, t) {
	if (e == null) return {};
	var n = {}, r = Object.keys(e), i, a;
	for (a = 0; a < r.length; a++) i = r[a], !(t.indexOf(i) >= 0) && (n[i] = e[i]);
	return n;
}
function yr(e, t) {
	if (e == null) return {};
	var n = vr(e, t), r, i;
	if (Object.getOwnPropertySymbols) {
		var a = Object.getOwnPropertySymbols(e);
		for (i = 0; i < a.length; i++) r = a[i], !(t.indexOf(r) >= 0) && Object.prototype.propertyIsEnumerable.call(e, r) && (n[r] = e[r]);
	}
	return n;
}
var br = "1.14.0";
function xr(e) {
	if (typeof window < "u" && window.navigator) return !!/* @__PURE__ */ navigator.userAgent.match(e);
}
var Sr = xr(/(?:Trident.*rv[ :]?11\.|msie|iemobile|Windows Phone)/i), Cr = xr(/Edge/i), wr = xr(/firefox/i), Tr = xr(/safari/i) && !xr(/chrome/i) && !xr(/android/i), Er = xr(/iP(ad|od|hone)/i), Dr = xr(/chrome/i) && xr(/android/i), Or = {
	capture: !1,
	passive: !1
};
function R(e, t, n) {
	e.addEventListener(t, n, !Sr && Or);
}
function z(e, t, n) {
	e.removeEventListener(t, n, !Sr && Or);
}
function kr(e, t) {
	if (t) {
		if (t[0] === ">" && (t = t.substring(1)), e) try {
			if (e.matches) return e.matches(t);
			if (e.msMatchesSelector) return e.msMatchesSelector(t);
			if (e.webkitMatchesSelector) return e.webkitMatchesSelector(t);
		} catch {
			return !1;
		}
		return !1;
	}
}
function Ar(e) {
	return e.host && e !== document && e.host.nodeType ? e.host : e.parentNode;
}
function jr(e, t, n, r) {
	if (e) {
		n ||= document;
		do {
			if (t != null && (t[0] === ">" ? e.parentNode === n && kr(e, t) : kr(e, t)) || r && e === n) return e;
			if (e === n) break;
		} while (e = Ar(e));
	}
	return null;
}
var Mr = /\s+/g;
function B(e, t, n) {
	e && t && (e.classList ? e.classList[n ? "add" : "remove"](t) : e.className = ((" " + e.className + " ").replace(Mr, " ").replace(" " + t + " ", " ") + (n ? " " + t : "")).replace(Mr, " "));
}
function V(e, t, n) {
	var r = e && e.style;
	if (r) {
		if (n === void 0) return document.defaultView && document.defaultView.getComputedStyle ? n = document.defaultView.getComputedStyle(e, "") : e.currentStyle && (n = e.currentStyle), t === void 0 ? n : n[t];
		!(t in r) && t.indexOf("webkit") === -1 && (t = "-webkit-" + t), r[t] = n + (typeof n == "string" ? "" : "px");
	}
}
function Nr(e, t) {
	var n = "";
	if (typeof e == "string") n = e;
	else do {
		var r = V(e, "transform");
		r && r !== "none" && (n = r + " " + n);
	} while (!t && (e = e.parentNode));
	var i = window.DOMMatrix || window.WebKitCSSMatrix || window.CSSMatrix || window.MSCSSMatrix;
	return i && new i(n);
}
function Pr(e, t, n) {
	if (e) {
		var r = e.getElementsByTagName(t), i = 0, a = r.length;
		if (n) for (; i < a; i++) n(r[i], i);
		return r;
	}
	return [];
}
function Fr() {
	return document.scrollingElement || document.documentElement;
}
function H(e, t, n, r, i) {
	if (!(!e.getBoundingClientRect && e !== window)) {
		var a, o, s, c, l, u, d;
		if (e !== window && e.parentNode && e !== Fr() ? (a = e.getBoundingClientRect(), o = a.top, s = a.left, c = a.bottom, l = a.right, u = a.height, d = a.width) : (o = 0, s = 0, c = window.innerHeight, l = window.innerWidth, u = window.innerHeight, d = window.innerWidth), (t || n) && e !== window && (i ||= e.parentNode, !Sr)) do
			if (i && i.getBoundingClientRect && (V(i, "transform") !== "none" || n && V(i, "position") !== "static")) {
				var f = i.getBoundingClientRect();
				o -= f.top + parseInt(V(i, "border-top-width")), s -= f.left + parseInt(V(i, "border-left-width")), c = o + a.height, l = s + a.width;
				break;
			}
		while (i = i.parentNode);
		if (r && e !== window) {
			var p = Nr(i || e), m = p && p.a, h = p && p.d;
			p && (o /= h, s /= m, d /= m, u /= h, c = o + u, l = s + d);
		}
		return {
			top: o,
			left: s,
			bottom: c,
			right: l,
			width: d,
			height: u
		};
	}
}
function Ir(e, t, n) {
	for (var r = Hr(e, !0), i = H(e)[t]; r;) {
		var a = H(r)[n], o = void 0;
		if (o = n === "top" || n === "left" ? i >= a : i <= a, !o) return r;
		if (r === Fr()) break;
		r = Hr(r, !1);
	}
	return !1;
}
function Lr(e, t, n, r) {
	for (var i = 0, a = 0, o = e.children; a < o.length;) {
		if (o[a].style.display !== "none" && o[a] !== Q.ghost && (r || o[a] !== Q.dragged) && jr(o[a], n.draggable, e, !1)) {
			if (i === t) return o[a];
			i++;
		}
		a++;
	}
	return null;
}
function Rr(e, t) {
	for (var n = e.lastElementChild; n && (n === Q.ghost || V(n, "display") === "none" || t && !kr(n, t));) n = n.previousElementSibling;
	return n || null;
}
function zr(e, t) {
	var n = 0;
	if (!e || !e.parentNode) return -1;
	for (; e = e.previousElementSibling;) e.nodeName.toUpperCase() !== "TEMPLATE" && e !== Q.clone && (!t || kr(e, t)) && n++;
	return n;
}
function Br(e) {
	var t = 0, n = 0, r = Fr();
	if (e) do {
		var i = Nr(e), a = i.a, o = i.d;
		t += e.scrollLeft * a, n += e.scrollTop * o;
	} while (e !== r && (e = e.parentNode));
	return [t, n];
}
function Vr(e, t) {
	for (var n in e) if (e.hasOwnProperty(n)) {
		for (var r in t) if (t.hasOwnProperty(r) && t[r] === e[n][r]) return Number(n);
	}
	return -1;
}
function Hr(e, t) {
	if (!e || !e.getBoundingClientRect) return Fr();
	var n = e, r = !1;
	do
		if (n.clientWidth < n.scrollWidth || n.clientHeight < n.scrollHeight) {
			var i = V(n);
			if (n.clientWidth < n.scrollWidth && (i.overflowX == "auto" || i.overflowX == "scroll") || n.clientHeight < n.scrollHeight && (i.overflowY == "auto" || i.overflowY == "scroll")) {
				if (!n.getBoundingClientRect || n === document.body) return Fr();
				if (r || t) return n;
				r = !0;
			}
		}
	while (n = n.parentNode);
	return Fr();
}
function Ur(e, t) {
	if (e && t) for (var n in t) t.hasOwnProperty(n) && (e[n] = t[n]);
	return e;
}
function Wr(e, t) {
	return Math.round(e.top) === Math.round(t.top) && Math.round(e.left) === Math.round(t.left) && Math.round(e.height) === Math.round(t.height) && Math.round(e.width) === Math.round(t.width);
}
var Gr;
function Kr(e, t) {
	return function() {
		if (!Gr) {
			var n = arguments, r = this;
			n.length === 1 ? e.call(r, n[0]) : e.apply(r, n), Gr = setTimeout(function() {
				Gr = void 0;
			}, t);
		}
	};
}
function qr() {
	clearTimeout(Gr), Gr = void 0;
}
function Jr(e, t, n) {
	e.scrollLeft += t, e.scrollTop += n;
}
function Yr(e) {
	var t = window.Polymer, n = window.jQuery || window.Zepto;
	return t && t.dom ? t.dom(e).cloneNode(!0) : n ? n(e).clone(!0)[0] : e.cloneNode(!0);
}
var Xr = "Sortable" + (/* @__PURE__ */ new Date()).getTime();
function Zr() {
	var e = [], t;
	return {
		captureAnimationState: function() {
			e = [], this.options.animation && [].slice.call(this.el.children).forEach(function(t) {
				if (!(V(t, "display") === "none" || t === Q.ghost)) {
					e.push({
						target: t,
						rect: H(t)
					});
					var n = mr({}, e[e.length - 1].rect);
					if (t.thisAnimationDuration) {
						var r = Nr(t, !0);
						r && (n.top -= r.f, n.left -= r.e);
					}
					t.fromRect = n;
				}
			});
		},
		addAnimationState: function(t) {
			e.push(t);
		},
		removeAnimationState: function(t) {
			e.splice(Vr(e, { target: t }), 1);
		},
		animateAll: function(n) {
			var r = this;
			if (!this.options.animation) {
				clearTimeout(t), typeof n == "function" && n();
				return;
			}
			var i = !1, a = 0;
			e.forEach(function(e) {
				var t = 0, n = e.target, o = n.fromRect, s = H(n), c = n.prevFromRect, l = n.prevToRect, u = e.rect, d = Nr(n, !0);
				d && (s.top -= d.f, s.left -= d.e), n.toRect = s, n.thisAnimationDuration && Wr(c, s) && !Wr(o, s) && (u.top - s.top) / (u.left - s.left) === (o.top - s.top) / (o.left - s.left) && (t = $r(u, c, l, r.options)), Wr(s, o) || (n.prevFromRect = o, n.prevToRect = s, t ||= r.options.animation, r.animate(n, u, s, t)), t && (i = !0, a = Math.max(a, t), clearTimeout(n.animationResetTimer), n.animationResetTimer = setTimeout(function() {
					n.animationTime = 0, n.prevFromRect = null, n.fromRect = null, n.prevToRect = null, n.thisAnimationDuration = null;
				}, t), n.thisAnimationDuration = t);
			}), clearTimeout(t), i ? t = setTimeout(function() {
				typeof n == "function" && n();
			}, a) : typeof n == "function" && n(), e = [];
		},
		animate: function(e, t, n, r) {
			if (r) {
				V(e, "transition", ""), V(e, "transform", "");
				var i = Nr(this.el), a = i && i.a, o = i && i.d, s = (t.left - n.left) / (a || 1), c = (t.top - n.top) / (o || 1);
				e.animatingX = !!s, e.animatingY = !!c, V(e, "transform", "translate3d(" + s + "px," + c + "px,0)"), this.forRepaintDummy = Qr(e), V(e, "transition", "transform " + r + "ms" + (this.options.easing ? " " + this.options.easing : "")), V(e, "transform", "translate3d(0,0,0)"), typeof e.animated == "number" && clearTimeout(e.animated), e.animated = setTimeout(function() {
					V(e, "transition", ""), V(e, "transform", ""), e.animated = !1, e.animatingX = !1, e.animatingY = !1;
				}, r);
			}
		}
	};
}
function Qr(e) {
	return e.offsetWidth;
}
function $r(e, t, n, r) {
	return Math.sqrt((t.top - e.top) ** 2 + (t.left - e.left) ** 2) / Math.sqrt((t.top - n.top) ** 2 + (t.left - n.left) ** 2) * r.animation;
}
var ei = [], ti = { initializeByDefault: !0 }, ni = {
	mount: function(e) {
		for (var t in ti) ti.hasOwnProperty(t) && !(t in e) && (e[t] = ti[t]);
		ei.forEach(function(t) {
			if (t.pluginName === e.pluginName) throw `Sortable: Cannot mount plugin ${e.pluginName} more than once`;
		}), ei.push(e);
	},
	pluginEvent: function(e, t, n) {
		var r = this;
		this.eventCanceled = !1, n.cancel = function() {
			r.eventCanceled = !0;
		};
		var i = e + "Global";
		ei.forEach(function(r) {
			t[r.pluginName] && (t[r.pluginName][i] && t[r.pluginName][i](mr({ sortable: t }, n)), t.options[r.pluginName] && t[r.pluginName][e] && t[r.pluginName][e](mr({ sortable: t }, n)));
		});
	},
	initializePlugins: function(e, t, n, r) {
		for (var i in ei.forEach(function(r) {
			var i = r.pluginName;
			if (!(!e.options[i] && !r.initializeByDefault)) {
				var a = new r(e, t, e.options);
				a.sortable = e, a.options = e.options, e[i] = a, _r(n, a.defaults);
			}
		}), e.options) if (e.options.hasOwnProperty(i)) {
			var a = this.modifyOption(e, i, e.options[i]);
			a !== void 0 && (e.options[i] = a);
		}
	},
	getEventProperties: function(e, t) {
		var n = {};
		return ei.forEach(function(r) {
			typeof r.eventProperties == "function" && _r(n, r.eventProperties.call(t[r.pluginName], e));
		}), n;
	},
	modifyOption: function(e, t, n) {
		var r;
		return ei.forEach(function(i) {
			e[i.pluginName] && i.optionListeners && typeof i.optionListeners[t] == "function" && (r = i.optionListeners[t].call(e[i.pluginName], n));
		}), r;
	}
};
function ri(e) {
	var t = e.sortable, n = e.rootEl, r = e.name, i = e.targetEl, a = e.cloneEl, o = e.toEl, s = e.fromEl, c = e.oldIndex, l = e.newIndex, u = e.oldDraggableIndex, d = e.newDraggableIndex, f = e.originalEvent, p = e.putSortable, m = e.extraEventProperties;
	if (t ||= n && n[Xr], t) {
		var h, g = t.options, _ = "on" + r.charAt(0).toUpperCase() + r.substr(1);
		window.CustomEvent && !Sr && !Cr ? h = new CustomEvent(r, {
			bubbles: !0,
			cancelable: !0
		}) : (h = document.createEvent("Event"), h.initEvent(r, !0, !0)), h.to = o || n, h.from = s || n, h.item = i || n, h.clone = a, h.oldIndex = c, h.newIndex = l, h.oldDraggableIndex = u, h.newDraggableIndex = d, h.originalEvent = f, h.pullMode = p ? p.lastPutMode : void 0;
		var v = mr(mr({}, m), ni.getEventProperties(r, t));
		for (var y in v) h[y] = v[y];
		n && n.dispatchEvent(h), g[_] && g[_].call(t, h);
	}
}
var ii = ["evt"], U = function(e, t) {
	var n = arguments.length > 2 && arguments[2] !== void 0 ? arguments[2] : {}, r = n.evt, i = yr(n, ii);
	ni.pluginEvent.bind(Q)(e, t, mr({
		dragEl: G,
		parentEl: K,
		ghostEl: q,
		rootEl: J,
		nextEl: ai,
		lastDownEl: oi,
		cloneEl: Y,
		cloneHidden: si,
		dragStarted: Si,
		putSortable: X,
		activeSortable: Q.active,
		originalEvent: r,
		oldIndex: ci,
		oldDraggableIndex: ui,
		newIndex: li,
		newDraggableIndex: di,
		hideGhostForTarget: Bi,
		unhideGhostForTarget: Vi,
		cloneNowHidden: function() {
			si = !0;
		},
		cloneNowShown: function() {
			si = !1;
		},
		dispatchSortableEvent: function(e) {
			W({
				sortable: t,
				name: e,
				originalEvent: r
			});
		}
	}, i));
};
function W(e) {
	ri(mr({
		putSortable: X,
		cloneEl: Y,
		targetEl: G,
		rootEl: J,
		oldIndex: ci,
		oldDraggableIndex: ui,
		newIndex: li,
		newDraggableIndex: di
	}, e));
}
var G, K, q, J, ai, oi, Y, si, ci, li, ui, di, fi, X, pi = !1, mi = !1, hi = [], gi, _i, vi, yi, bi, xi, Si, Ci, wi, Ti = !1, Ei = !1, Di, Z, Oi = [], ki = !1, Ai = [], ji = typeof document < "u", Mi = Er, Ni = Cr || Sr ? "cssFloat" : "float", Pi = ji && !Dr && !Er && "draggable" in document.createElement("div"), Fi = function() {
	if (ji) {
		if (Sr) return !1;
		var e = document.createElement("x");
		return e.style.cssText = "pointer-events:auto", e.style.pointerEvents === "auto";
	}
}(), Ii = function(e, t) {
	var n = V(e), r = parseInt(n.width) - parseInt(n.paddingLeft) - parseInt(n.paddingRight) - parseInt(n.borderLeftWidth) - parseInt(n.borderRightWidth), i = Lr(e, 0, t), a = Lr(e, 1, t), o = i && V(i), s = a && V(a), c = o && parseInt(o.marginLeft) + parseInt(o.marginRight) + H(i).width, l = s && parseInt(s.marginLeft) + parseInt(s.marginRight) + H(a).width;
	if (n.display === "flex") return n.flexDirection === "column" || n.flexDirection === "column-reverse" ? "vertical" : "horizontal";
	if (n.display === "grid") return n.gridTemplateColumns.split(" ").length <= 1 ? "vertical" : "horizontal";
	if (i && o.float && o.float !== "none") {
		var u = o.float === "left" ? "left" : "right";
		return a && (s.clear === "both" || s.clear === u) ? "vertical" : "horizontal";
	}
	return i && (o.display === "block" || o.display === "flex" || o.display === "table" || o.display === "grid" || c >= r && n[Ni] === "none" || a && n[Ni] === "none" && c + l > r) ? "vertical" : "horizontal";
}, Li = function(e, t, n) {
	var r = n ? e.left : e.top, i = n ? e.right : e.bottom, a = n ? e.width : e.height, o = n ? t.left : t.top, s = n ? t.right : t.bottom, c = n ? t.width : t.height;
	return r === o || i === s || r + a / 2 === o + c / 2;
}, Ri = function(e, t) {
	var n;
	return hi.some(function(r) {
		var i = r[Xr].options.emptyInsertThreshold;
		if (!(!i || Rr(r))) {
			var a = H(r), o = e >= a.left - i && e <= a.right + i, s = t >= a.top - i && t <= a.bottom + i;
			if (o && s) return n = r;
		}
	}), n;
}, zi = function(e) {
	function t(e, n) {
		return function(r, i, a, o) {
			var s = r.options.group.name && i.options.group.name && r.options.group.name === i.options.group.name;
			if (e == null && (n || s)) return !0;
			if (e == null || e === !1) return !1;
			if (n && e === "clone") return e;
			if (typeof e == "function") return t(e(r, i, a, o), n)(r, i, a, o);
			var c = (n ? r : i).options.group.name;
			return e === !0 || typeof e == "string" && e === c || e.join && e.indexOf(c) > -1;
		};
	}
	var n = {}, r = e.group;
	(!r || hr(r) != "object") && (r = { name: r }), n.name = r.name, n.checkPull = t(r.pull, !0), n.checkPut = t(r.put), n.revertClone = r.revertClone, e.group = n;
}, Bi = function() {
	!Fi && q && V(q, "display", "none");
}, Vi = function() {
	!Fi && q && V(q, "display", "");
};
ji && document.addEventListener("click", function(e) {
	if (mi) return e.preventDefault(), e.stopPropagation && e.stopPropagation(), e.stopImmediatePropagation && e.stopImmediatePropagation(), mi = !1, !1;
}, !0);
var Hi = function(e) {
	if (G) {
		e = e.touches ? e.touches[0] : e;
		var t = Ri(e.clientX, e.clientY);
		if (t) {
			var n = {};
			for (var r in e) e.hasOwnProperty(r) && (n[r] = e[r]);
			n.target = n.rootEl = t, n.preventDefault = void 0, n.stopPropagation = void 0, t[Xr]._onDragOver(n);
		}
	}
}, Ui = function(e) {
	G && G.parentNode[Xr]._isOutsideThisEl(e.target);
};
function Q(e, t) {
	if (!(e && e.nodeType && e.nodeType === 1)) throw `Sortable: \`el\` must be an HTMLElement, not ${{}.toString.call(e)}`;
	this.el = e, this.options = t = _r({}, t), e[Xr] = this;
	var n = {
		group: null,
		sort: !0,
		disabled: !1,
		store: null,
		handle: null,
		draggable: /^[uo]l$/i.test(e.nodeName) ? ">li" : ">*",
		swapThreshold: 1,
		invertSwap: !1,
		invertedSwapThreshold: null,
		removeCloneOnHide: !0,
		direction: function() {
			return Ii(e, this.options);
		},
		ghostClass: "sortable-ghost",
		chosenClass: "sortable-chosen",
		dragClass: "sortable-drag",
		ignore: "a, img",
		filter: null,
		preventOnFilter: !0,
		animation: 0,
		easing: null,
		setData: function(e, t) {
			e.setData("Text", t.textContent);
		},
		dropBubble: !1,
		dragoverBubble: !1,
		dataIdAttr: "data-id",
		delay: 0,
		delayOnTouchOnly: !1,
		touchStartThreshold: (Number.parseInt ? Number : window).parseInt(window.devicePixelRatio, 10) || 1,
		forceFallback: !1,
		fallbackClass: "sortable-fallback",
		fallbackOnBody: !1,
		fallbackTolerance: 0,
		fallbackOffset: {
			x: 0,
			y: 0
		},
		supportPointer: Q.supportPointer !== !1 && "PointerEvent" in window && !Tr,
		emptyInsertThreshold: 5
	};
	for (var r in ni.initializePlugins(this, e, n), n) !(r in t) && (t[r] = n[r]);
	for (var i in zi(t), this) i.charAt(0) === "_" && typeof this[i] == "function" && (this[i] = this[i].bind(this));
	this.nativeDraggable = t.forceFallback ? !1 : Pi, this.nativeDraggable && (this.options.touchStartThreshold = 1), t.supportPointer ? R(e, "pointerdown", this._onTapStart) : (R(e, "mousedown", this._onTapStart), R(e, "touchstart", this._onTapStart)), this.nativeDraggable && (R(e, "dragover", this), R(e, "dragenter", this)), hi.push(this.el), t.store && t.store.get && this.sort(t.store.get(this) || []), _r(this, Zr());
}
Q.prototype = {
	constructor: Q,
	_isOutsideThisEl: function(e) {
		!this.el.contains(e) && e !== this.el && (Ci = null);
	},
	_getDirection: function(e, t) {
		return typeof this.options.direction == "function" ? this.options.direction.call(this, e, t, G) : this.options.direction;
	},
	_onTapStart: function(e) {
		if (e.cancelable) {
			var t = this, n = this.el, r = this.options, i = r.preventOnFilter, a = e.type, o = e.touches && e.touches[0] || e.pointerType && e.pointerType === "touch" && e, s = (o || e).target, c = e.target.shadowRoot && (e.path && e.path[0] || e.composedPath && e.composedPath()[0]) || s, l = r.filter;
			if ($i(n), !G && !(/mousedown|pointerdown/.test(a) && e.button !== 0 || r.disabled) && !c.isContentEditable && !(!this.nativeDraggable && Tr && s && s.tagName.toUpperCase() === "SELECT") && (s = jr(s, r.draggable, n, !1), !(s && s.animated) && oi !== s)) {
				if (ci = zr(s), ui = zr(s, r.draggable), typeof l == "function") {
					if (l.call(this, e, s, this)) {
						W({
							sortable: t,
							rootEl: c,
							name: "filter",
							targetEl: s,
							toEl: n,
							fromEl: n
						}), U("filter", t, { evt: e }), i && e.cancelable && e.preventDefault();
						return;
					}
				} else if (l && (l = l.split(",").some(function(r) {
					if (r = jr(c, r.trim(), n, !1), r) return W({
						sortable: t,
						rootEl: r,
						name: "filter",
						targetEl: s,
						fromEl: n,
						toEl: n
					}), U("filter", t, { evt: e }), !0;
				}), l)) {
					i && e.cancelable && e.preventDefault();
					return;
				}
				r.handle && !jr(c, r.handle, n, !1) || this._prepareDragStart(e, o, s);
			}
		}
	},
	_prepareDragStart: function(e, t, n) {
		var r = this, i = r.el, a = r.options, o = i.ownerDocument, s;
		if (n && !G && n.parentNode === i) {
			var c = H(n);
			if (J = i, G = n, K = G.parentNode, ai = G.nextSibling, oi = n, fi = a.group, Q.dragged = G, gi = {
				target: G,
				clientX: (t || e).clientX,
				clientY: (t || e).clientY
			}, bi = gi.clientX - c.left, xi = gi.clientY - c.top, this._lastX = (t || e).clientX, this._lastY = (t || e).clientY, G.style["will-change"] = "all", s = function() {
				if (U("delayEnded", r, { evt: e }), Q.eventCanceled) {
					r._onDrop();
					return;
				}
				r._disableDelayedDragEvents(), !wr && r.nativeDraggable && (G.draggable = !0), r._triggerDragStart(e, t), W({
					sortable: r,
					name: "choose",
					originalEvent: e
				}), B(G, a.chosenClass, !0);
			}, a.ignore.split(",").forEach(function(e) {
				Pr(G, e.trim(), Ki);
			}), R(o, "dragover", Hi), R(o, "mousemove", Hi), R(o, "touchmove", Hi), R(o, "mouseup", r._onDrop), R(o, "touchend", r._onDrop), R(o, "touchcancel", r._onDrop), wr && this.nativeDraggable && (this.options.touchStartThreshold = 4, G.draggable = !0), U("delayStart", this, { evt: e }), a.delay && (!a.delayOnTouchOnly || t) && (!this.nativeDraggable || !(Cr || Sr))) {
				if (Q.eventCanceled) {
					this._onDrop();
					return;
				}
				R(o, "mouseup", r._disableDelayedDrag), R(o, "touchend", r._disableDelayedDrag), R(o, "touchcancel", r._disableDelayedDrag), R(o, "mousemove", r._delayedDragTouchMoveHandler), R(o, "touchmove", r._delayedDragTouchMoveHandler), a.supportPointer && R(o, "pointermove", r._delayedDragTouchMoveHandler), r._dragStartTimer = setTimeout(s, a.delay);
			} else s();
		}
	},
	_delayedDragTouchMoveHandler: function(e) {
		var t = e.touches ? e.touches[0] : e;
		Math.max(Math.abs(t.clientX - this._lastX), Math.abs(t.clientY - this._lastY)) >= Math.floor(this.options.touchStartThreshold / (this.nativeDraggable && window.devicePixelRatio || 1)) && this._disableDelayedDrag();
	},
	_disableDelayedDrag: function() {
		G && Ki(G), clearTimeout(this._dragStartTimer), this._disableDelayedDragEvents();
	},
	_disableDelayedDragEvents: function() {
		var e = this.el.ownerDocument;
		z(e, "mouseup", this._disableDelayedDrag), z(e, "touchend", this._disableDelayedDrag), z(e, "touchcancel", this._disableDelayedDrag), z(e, "mousemove", this._delayedDragTouchMoveHandler), z(e, "touchmove", this._delayedDragTouchMoveHandler), z(e, "pointermove", this._delayedDragTouchMoveHandler);
	},
	_triggerDragStart: function(e, t) {
		t ||= e.pointerType == "touch" && e, !this.nativeDraggable || t ? this.options.supportPointer ? R(document, "pointermove", this._onTouchMove) : t ? R(document, "touchmove", this._onTouchMove) : R(document, "mousemove", this._onTouchMove) : (R(G, "dragend", this), R(J, "dragstart", this._onDragStart));
		try {
			document.selection ? ea(function() {
				document.selection.empty();
			}) : window.getSelection().removeAllRanges();
		} catch {}
	},
	_dragStarted: function(e, t) {
		if (pi = !1, J && G) {
			U("dragStarted", this, { evt: t }), this.nativeDraggable && R(document, "dragover", Ui);
			var n = this.options;
			!e && B(G, n.dragClass, !1), B(G, n.ghostClass, !0), Q.active = this, e && this._appendGhost(), W({
				sortable: this,
				name: "start",
				originalEvent: t
			});
		} else this._nulling();
	},
	_emulateDragOver: function() {
		if (_i) {
			this._lastX = _i.clientX, this._lastY = _i.clientY, Bi();
			for (var e = document.elementFromPoint(_i.clientX, _i.clientY), t = e; e && e.shadowRoot && (e = e.shadowRoot.elementFromPoint(_i.clientX, _i.clientY), e !== t);) t = e;
			if (G.parentNode[Xr]._isOutsideThisEl(e), t) do {
				if (t[Xr]) {
					var n = void 0;
					if (n = t[Xr]._onDragOver({
						clientX: _i.clientX,
						clientY: _i.clientY,
						target: e,
						rootEl: t
					}), n && !this.options.dragoverBubble) break;
				}
				e = t;
			} while (t = t.parentNode);
			Vi();
		}
	},
	_onTouchMove: function(e) {
		if (gi) {
			var t = this.options, n = t.fallbackTolerance, r = t.fallbackOffset, i = e.touches ? e.touches[0] : e, a = q && Nr(q, !0), o = q && a && a.a, s = q && a && a.d, c = Mi && Z && Br(Z), l = (i.clientX - gi.clientX + r.x) / (o || 1) + (c ? c[0] - Oi[0] : 0) / (o || 1), u = (i.clientY - gi.clientY + r.y) / (s || 1) + (c ? c[1] - Oi[1] : 0) / (s || 1);
			if (!Q.active && !pi) {
				if (n && Math.max(Math.abs(i.clientX - this._lastX), Math.abs(i.clientY - this._lastY)) < n) return;
				this._onDragStart(e, !0);
			}
			if (q) {
				a ? (a.e += l - (vi || 0), a.f += u - (yi || 0)) : a = {
					a: 1,
					b: 0,
					c: 0,
					d: 1,
					e: l,
					f: u
				};
				var d = `matrix(${a.a},${a.b},${a.c},${a.d},${a.e},${a.f})`;
				V(q, "webkitTransform", d), V(q, "mozTransform", d), V(q, "msTransform", d), V(q, "transform", d), vi = l, yi = u, _i = i;
			}
			e.cancelable && e.preventDefault();
		}
	},
	_appendGhost: function() {
		if (!q) {
			var e = this.options.fallbackOnBody ? document.body : J, t = H(G, !0, Mi, !0, e), n = this.options;
			if (Mi) {
				for (Z = e; V(Z, "position") === "static" && V(Z, "transform") === "none" && Z !== document;) Z = Z.parentNode;
				Z !== document.body && Z !== document.documentElement ? (Z === document && (Z = Fr()), t.top += Z.scrollTop, t.left += Z.scrollLeft) : Z = Fr(), Oi = Br(Z);
			}
			q = G.cloneNode(!0), B(q, n.ghostClass, !1), B(q, n.fallbackClass, !0), B(q, n.dragClass, !0), V(q, "transition", ""), V(q, "transform", ""), V(q, "box-sizing", "border-box"), V(q, "margin", 0), V(q, "top", t.top), V(q, "left", t.left), V(q, "width", t.width), V(q, "height", t.height), V(q, "opacity", "0.8"), V(q, "position", Mi ? "absolute" : "fixed"), V(q, "zIndex", "100000"), V(q, "pointerEvents", "none"), Q.ghost = q, e.appendChild(q), V(q, "transform-origin", bi / parseInt(q.style.width) * 100 + "% " + xi / parseInt(q.style.height) * 100 + "%");
		}
	},
	_onDragStart: function(e, t) {
		var n = this, r = e.dataTransfer, i = n.options;
		if (U("dragStart", this, { evt: e }), Q.eventCanceled) {
			this._onDrop();
			return;
		}
		U("setupClone", this), Q.eventCanceled || (Y = Yr(G), Y.draggable = !1, Y.style["will-change"] = "", this._hideClone(), B(Y, this.options.chosenClass, !1), Q.clone = Y), n.cloneId = ea(function() {
			U("clone", n), !Q.eventCanceled && (n.options.removeCloneOnHide || J.insertBefore(Y, G), n._hideClone(), W({
				sortable: n,
				name: "clone"
			}));
		}), !t && B(G, i.dragClass, !0), t ? (mi = !0, n._loopId = setInterval(n._emulateDragOver, 50)) : (z(document, "mouseup", n._onDrop), z(document, "touchend", n._onDrop), z(document, "touchcancel", n._onDrop), r && (r.effectAllowed = "move", i.setData && i.setData.call(n, r, G)), R(document, "drop", n), V(G, "transform", "translateZ(0)")), pi = !0, n._dragStartId = ea(n._dragStarted.bind(n, t, e)), R(document, "selectstart", n), Si = !0, Tr && V(document.body, "user-select", "none");
	},
	_onDragOver: function(e) {
		var t = this.el, n = e.target, r, i, a, o = this.options, s = o.group, c = Q.active, l = fi === s, u = o.sort, d = X || c, f, p = this, m = !1;
		if (ki) return;
		function h(o, s) {
			U(o, p, mr({
				evt: e,
				isOwner: l,
				axis: f ? "vertical" : "horizontal",
				revert: a,
				dragRect: r,
				targetRect: i,
				canSort: u,
				fromSortable: d,
				target: n,
				completed: _,
				onMove: function(n, i) {
					return Gi(J, t, G, r, n, H(n), e, i);
				},
				changed: v
			}, s));
		}
		function g() {
			h("dragOverAnimationCapture"), p.captureAnimationState(), p !== d && d.captureAnimationState();
		}
		function _(r) {
			return h("dragOverCompleted", { insertion: r }), r && (l ? c._hideClone() : c._showClone(p), p !== d && (B(G, X ? X.options.ghostClass : c.options.ghostClass, !1), B(G, o.ghostClass, !0)), X !== p && p !== Q.active ? X = p : p === Q.active && X && (X = null), d === p && (p._ignoreWhileAnimating = n), p.animateAll(function() {
				h("dragOverAnimationComplete"), p._ignoreWhileAnimating = null;
			}), p !== d && (d.animateAll(), d._ignoreWhileAnimating = null)), (n === G && !G.animated || n === t && !n.animated) && (Ci = null), !o.dragoverBubble && !e.rootEl && n !== document && (G.parentNode[Xr]._isOutsideThisEl(e.target), !r && Hi(e)), !o.dragoverBubble && e.stopPropagation && e.stopPropagation(), m = !0;
		}
		function v() {
			li = zr(G), di = zr(G, o.draggable), W({
				sortable: p,
				name: "change",
				toEl: t,
				newIndex: li,
				newDraggableIndex: di,
				originalEvent: e
			});
		}
		if (e.preventDefault !== void 0 && e.cancelable && e.preventDefault(), n = jr(n, o.draggable, t, !0), h("dragOver"), Q.eventCanceled) return m;
		if (G.contains(e.target) || n.animated && n.animatingX && n.animatingY || p._ignoreWhileAnimating === n) return _(!1);
		if (mi = !1, c && !o.disabled && (l ? u || (a = K !== J) : X === this || (this.lastPutMode = fi.checkPull(this, c, G, e)) && s.checkPut(this, c, G, e))) {
			if (f = this._getDirection(e, n) === "vertical", r = H(G), h("dragOverValid"), Q.eventCanceled) return m;
			if (a) return K = J, g(), this._hideClone(), h("revert"), Q.eventCanceled || (ai ? J.insertBefore(G, ai) : J.appendChild(G)), _(!0);
			var y = Rr(t, o.draggable);
			if (!y || Yi(e, f, this) && !y.animated) {
				if (y === G) return _(!1);
				if (y && t === e.target && (n = y), n && (i = H(n)), Gi(J, t, G, r, n, i, e, !!n) !== !1) return g(), t.appendChild(G), K = t, v(), _(!0);
			} else if (y && Ji(e, f, this)) {
				var b = Lr(t, 0, o, !0);
				if (b === G) return _(!1);
				if (n = b, i = H(n), Gi(J, t, G, r, n, i, e, !1) !== !1) return g(), t.insertBefore(G, b), K = t, v(), _(!0);
			} else if (n.parentNode === t) {
				i = H(n);
				var x = 0, S, C = G.parentNode !== t, w = !Li(G.animated && G.toRect || r, n.animated && n.toRect || i, f), T = f ? "top" : "left", E = Ir(n, "top", "top") || Ir(G, "top", "top"), D = E ? E.scrollTop : void 0;
				Ci !== n && (S = i[T], Ti = !1, Ei = !w && o.invertSwap || C), x = Xi(e, n, i, f, w ? 1 : o.swapThreshold, o.invertedSwapThreshold == null ? o.swapThreshold : o.invertedSwapThreshold, Ei, Ci === n);
				var O;
				if (x !== 0) {
					var k = zr(G);
					do
						k -= x, O = K.children[k];
					while (O && (V(O, "display") === "none" || O === q));
				}
				if (x === 0 || O === n) return _(!1);
				Ci = n, wi = x;
				var A = n.nextElementSibling, j = !1;
				j = x === 1;
				var M = Gi(J, t, G, r, n, i, e, j);
				if (M !== !1) return (M === 1 || M === -1) && (j = M === 1), ki = !0, setTimeout(qi, 30), g(), j && !A ? t.appendChild(G) : n.parentNode.insertBefore(G, j ? A : n), E && Jr(E, 0, D - E.scrollTop), K = G.parentNode, S !== void 0 && !Ei && (Di = Math.abs(S - H(n)[T])), v(), _(!0);
			}
			if (t.contains(G)) return _(!1);
		}
		return !1;
	},
	_ignoreWhileAnimating: null,
	_offMoveEvents: function() {
		z(document, "mousemove", this._onTouchMove), z(document, "touchmove", this._onTouchMove), z(document, "pointermove", this._onTouchMove), z(document, "dragover", Hi), z(document, "mousemove", Hi), z(document, "touchmove", Hi);
	},
	_offUpEvents: function() {
		var e = this.el.ownerDocument;
		z(e, "mouseup", this._onDrop), z(e, "touchend", this._onDrop), z(e, "pointerup", this._onDrop), z(e, "touchcancel", this._onDrop), z(document, "selectstart", this);
	},
	_onDrop: function(e) {
		var t = this.el, n = this.options;
		if (li = zr(G), di = zr(G, n.draggable), U("drop", this, { evt: e }), K = G && G.parentNode, li = zr(G), di = zr(G, n.draggable), Q.eventCanceled) {
			this._nulling();
			return;
		}
		pi = !1, Ei = !1, Ti = !1, clearInterval(this._loopId), clearTimeout(this._dragStartTimer), ta(this.cloneId), ta(this._dragStartId), this.nativeDraggable && (z(document, "drop", this), z(t, "dragstart", this._onDragStart)), this._offMoveEvents(), this._offUpEvents(), Tr && V(document.body, "user-select", ""), V(G, "transform", ""), e && (Si && (e.cancelable && e.preventDefault(), !n.dropBubble && e.stopPropagation()), q && q.parentNode && q.parentNode.removeChild(q), (J === K || X && X.lastPutMode !== "clone") && Y && Y.parentNode && Y.parentNode.removeChild(Y), G && (this.nativeDraggable && z(G, "dragend", this), Ki(G), G.style["will-change"] = "", Si && !pi && B(G, X ? X.options.ghostClass : this.options.ghostClass, !1), B(G, this.options.chosenClass, !1), W({
			sortable: this,
			name: "unchoose",
			toEl: K,
			newIndex: null,
			newDraggableIndex: null,
			originalEvent: e
		}), J === K ? li !== ci && li >= 0 && (W({
			sortable: this,
			name: "update",
			toEl: K,
			originalEvent: e
		}), W({
			sortable: this,
			name: "sort",
			toEl: K,
			originalEvent: e
		})) : (li >= 0 && (W({
			rootEl: K,
			name: "add",
			toEl: K,
			fromEl: J,
			originalEvent: e
		}), W({
			sortable: this,
			name: "remove",
			toEl: K,
			originalEvent: e
		}), W({
			rootEl: K,
			name: "sort",
			toEl: K,
			fromEl: J,
			originalEvent: e
		}), W({
			sortable: this,
			name: "sort",
			toEl: K,
			originalEvent: e
		})), X && X.save()), Q.active && ((li == null || li === -1) && (li = ci, di = ui), W({
			sortable: this,
			name: "end",
			toEl: K,
			originalEvent: e
		}), this.save()))), this._nulling();
	},
	_nulling: function() {
		U("nulling", this), J = G = K = q = ai = Y = oi = si = gi = _i = Si = li = di = ci = ui = Ci = wi = X = fi = Q.dragged = Q.ghost = Q.clone = Q.active = null, Ai.forEach(function(e) {
			e.checked = !0;
		}), Ai.length = vi = yi = 0;
	},
	handleEvent: function(e) {
		switch (e.type) {
			case "drop":
			case "dragend":
				this._onDrop(e);
				break;
			case "dragenter":
			case "dragover":
				G && (this._onDragOver(e), Wi(e));
				break;
			case "selectstart":
				e.preventDefault();
				break;
		}
	},
	toArray: function() {
		for (var e = [], t, n = this.el.children, r = 0, i = n.length, a = this.options; r < i; r++) t = n[r], jr(t, a.draggable, this.el, !1) && e.push(t.getAttribute(a.dataIdAttr) || Qi(t));
		return e;
	},
	sort: function(e, t) {
		var n = {}, r = this.el;
		this.toArray().forEach(function(e, t) {
			var i = r.children[t];
			jr(i, this.options.draggable, r, !1) && (n[e] = i);
		}, this), t && this.captureAnimationState(), e.forEach(function(e) {
			n[e] && (r.removeChild(n[e]), r.appendChild(n[e]));
		}), t && this.animateAll();
	},
	save: function() {
		var e = this.options.store;
		e && e.set && e.set(this);
	},
	closest: function(e, t) {
		return jr(e, t || this.options.draggable, this.el, !1);
	},
	option: function(e, t) {
		var n = this.options;
		if (t === void 0) return n[e];
		var r = ni.modifyOption(this, e, t);
		r === void 0 ? n[e] = t : n[e] = r, e === "group" && zi(n);
	},
	destroy: function() {
		U("destroy", this);
		var e = this.el;
		e[Xr] = null, z(e, "mousedown", this._onTapStart), z(e, "touchstart", this._onTapStart), z(e, "pointerdown", this._onTapStart), this.nativeDraggable && (z(e, "dragover", this), z(e, "dragenter", this)), Array.prototype.forEach.call(e.querySelectorAll("[draggable]"), function(e) {
			e.removeAttribute("draggable");
		}), this._onDrop(), this._disableDelayedDragEvents(), hi.splice(hi.indexOf(this.el), 1), this.el = e = null;
	},
	_hideClone: function() {
		if (!si) {
			if (U("hideClone", this), Q.eventCanceled) return;
			V(Y, "display", "none"), this.options.removeCloneOnHide && Y.parentNode && Y.parentNode.removeChild(Y), si = !0;
		}
	},
	_showClone: function(e) {
		if (e.lastPutMode !== "clone") {
			this._hideClone();
			return;
		}
		if (si) {
			if (U("showClone", this), Q.eventCanceled) return;
			G.parentNode == J && !this.options.group.revertClone ? J.insertBefore(Y, G) : ai ? J.insertBefore(Y, ai) : J.appendChild(Y), this.options.group.revertClone && this.animate(G, Y), V(Y, "display", ""), si = !1;
		}
	}
};
function Wi(e) {
	e.dataTransfer && (e.dataTransfer.dropEffect = "move"), e.cancelable && e.preventDefault();
}
function Gi(e, t, n, r, i, a, o, s) {
	var c, l = e[Xr], u = l.options.onMove, d;
	return window.CustomEvent && !Sr && !Cr ? c = new CustomEvent("move", {
		bubbles: !0,
		cancelable: !0
	}) : (c = document.createEvent("Event"), c.initEvent("move", !0, !0)), c.to = t, c.from = e, c.dragged = n, c.draggedRect = r, c.related = i || t, c.relatedRect = a || H(t), c.willInsertAfter = s, c.originalEvent = o, e.dispatchEvent(c), u && (d = u.call(l, c, o)), d;
}
function Ki(e) {
	e.draggable = !1;
}
function qi() {
	ki = !1;
}
function Ji(e, t, n) {
	var r = H(Lr(n.el, 0, n.options, !0)), i = 10;
	return t ? e.clientX < r.left - i || e.clientY < r.top && e.clientX < r.right : e.clientY < r.top - i || e.clientY < r.bottom && e.clientX < r.left;
}
function Yi(e, t, n) {
	var r = H(Rr(n.el, n.options.draggable)), i = 10;
	return t ? e.clientX > r.right + i || e.clientX <= r.right && e.clientY > r.bottom && e.clientX >= r.left : e.clientX > r.right && e.clientY > r.top || e.clientX <= r.right && e.clientY > r.bottom + i;
}
function Xi(e, t, n, r, i, a, o, s) {
	var c = r ? e.clientY : e.clientX, l = r ? n.height : n.width, u = r ? n.top : n.left, d = r ? n.bottom : n.right, f = !1;
	if (!o) {
		if (s && Di < l * i) {
			if (!Ti && (wi === 1 ? c > u + l * a / 2 : c < d - l * a / 2) && (Ti = !0), Ti) f = !0;
			else if (wi === 1 ? c < u + Di : c > d - Di) return -wi;
		} else if (c > u + l * (1 - i) / 2 && c < d - l * (1 - i) / 2) return Zi(t);
	}
	return f ||= o, f && (c < u + l * a / 2 || c > d - l * a / 2) ? c > u + l / 2 ? 1 : -1 : 0;
}
function Zi(e) {
	return zr(G) < zr(e) ? 1 : -1;
}
function Qi(e) {
	for (var t = e.tagName + e.className + e.src + e.href + e.textContent, n = t.length, r = 0; n--;) r += t.charCodeAt(n);
	return r.toString(36);
}
function $i(e) {
	Ai.length = 0;
	for (var t = e.getElementsByTagName("input"), n = t.length; n--;) {
		var r = t[n];
		r.checked && Ai.push(r);
	}
}
function ea(e) {
	return setTimeout(e, 0);
}
function ta(e) {
	return clearTimeout(e);
}
ji && R(document, "touchmove", function(e) {
	(Q.active || pi) && e.cancelable && e.preventDefault();
}), Q.utils = {
	on: R,
	off: z,
	css: V,
	find: Pr,
	is: function(e, t) {
		return !!jr(e, t, e, !1);
	},
	extend: Ur,
	throttle: Kr,
	closest: jr,
	toggleClass: B,
	clone: Yr,
	index: zr,
	nextTick: ea,
	cancelNextTick: ta,
	detectDirection: Ii,
	getChild: Lr
}, Q.get = function(e) {
	return e[Xr];
}, Q.mount = function() {
	var e = [...arguments];
	e[0].constructor === Array && (e = e[0]), e.forEach(function(e) {
		if (!e.prototype || !e.prototype.constructor) throw `Sortable: Mounted plugin must be a constructor function, not ${{}.toString.call(e)}`;
		e.utils && (Q.utils = mr(mr({}, Q.utils), e.utils)), ni.mount(e);
	});
}, Q.create = function(e, t) {
	return new Q(e, t);
}, Q.version = br;
var $ = [], na, ra, ia = !1, aa, oa, sa, ca;
function la() {
	function e() {
		for (var e in this.defaults = {
			scroll: !0,
			forceAutoScrollFallback: !1,
			scrollSensitivity: 30,
			scrollSpeed: 10,
			bubbleScroll: !0
		}, this) e.charAt(0) === "_" && typeof this[e] == "function" && (this[e] = this[e].bind(this));
	}
	return e.prototype = {
		dragStarted: function(e) {
			var t = e.originalEvent;
			this.sortable.nativeDraggable ? R(document, "dragover", this._handleAutoScroll) : this.options.supportPointer ? R(document, "pointermove", this._handleFallbackAutoScroll) : t.touches ? R(document, "touchmove", this._handleFallbackAutoScroll) : R(document, "mousemove", this._handleFallbackAutoScroll);
		},
		dragOverCompleted: function(e) {
			var t = e.originalEvent;
			!this.options.dragOverBubble && !t.rootEl && this._handleAutoScroll(t);
		},
		drop: function() {
			this.sortable.nativeDraggable ? z(document, "dragover", this._handleAutoScroll) : (z(document, "pointermove", this._handleFallbackAutoScroll), z(document, "touchmove", this._handleFallbackAutoScroll), z(document, "mousemove", this._handleFallbackAutoScroll)), da(), ua(), qr();
		},
		nulling: function() {
			sa = ra = na = ia = ca = aa = oa = null, $.length = 0;
		},
		_handleFallbackAutoScroll: function(e) {
			this._handleAutoScroll(e, !0);
		},
		_handleAutoScroll: function(e, t) {
			var n = this, r = (e.touches ? e.touches[0] : e).clientX, i = (e.touches ? e.touches[0] : e).clientY, a = document.elementFromPoint(r, i);
			if (sa = e, t || this.options.forceAutoScrollFallback || Cr || Sr || Tr) {
				fa(e, this.options, a, t);
				var o = Hr(a, !0);
				ia && (!ca || r !== aa || i !== oa) && (ca && da(), ca = setInterval(function() {
					var a = Hr(document.elementFromPoint(r, i), !0);
					a !== o && (o = a, ua()), fa(e, n.options, a, t);
				}, 10), aa = r, oa = i);
			} else {
				if (!this.options.bubbleScroll || Hr(a, !0) === Fr()) {
					ua();
					return;
				}
				fa(e, this.options, Hr(a, !1), !1);
			}
		}
	}, _r(e, {
		pluginName: "scroll",
		initializeByDefault: !0
	});
}
function ua() {
	$.forEach(function(e) {
		clearInterval(e.pid);
	}), $ = [];
}
function da() {
	clearInterval(ca);
}
var fa = Kr(function(e, t, n, r) {
	if (t.scroll) {
		var i = (e.touches ? e.touches[0] : e).clientX, a = (e.touches ? e.touches[0] : e).clientY, o = t.scrollSensitivity, s = t.scrollSpeed, c = Fr(), l = !1, u;
		ra !== n && (ra = n, ua(), na = t.scroll, u = t.scrollFn, na === !0 && (na = Hr(n, !0)));
		var d = 0, f = na;
		do {
			var p = f, m = H(p), h = m.top, g = m.bottom, _ = m.left, v = m.right, y = m.width, b = m.height, x = void 0, S = void 0, C = p.scrollWidth, w = p.scrollHeight, T = V(p), E = p.scrollLeft, D = p.scrollTop;
			p === c ? (x = y < C && (T.overflowX === "auto" || T.overflowX === "scroll" || T.overflowX === "visible"), S = b < w && (T.overflowY === "auto" || T.overflowY === "scroll" || T.overflowY === "visible")) : (x = y < C && (T.overflowX === "auto" || T.overflowX === "scroll"), S = b < w && (T.overflowY === "auto" || T.overflowY === "scroll"));
			var O = x && (Math.abs(v - i) <= o && E + y < C) - (Math.abs(_ - i) <= o && !!E), k = S && (Math.abs(g - a) <= o && D + b < w) - (Math.abs(h - a) <= o && !!D);
			if (!$[d]) for (var A = 0; A <= d; A++) $[A] || ($[A] = {});
			($[d].vx != O || $[d].vy != k || $[d].el !== p) && ($[d].el = p, $[d].vx = O, $[d].vy = k, clearInterval($[d].pid), (O != 0 || k != 0) && (l = !0, $[d].pid = setInterval(function() {
				r && this.layer === 0 && Q.active._onTouchMove(sa);
				var t = $[this.layer].vy ? $[this.layer].vy * s : 0, n = $[this.layer].vx ? $[this.layer].vx * s : 0;
				typeof u == "function" && u.call(Q.dragged.parentNode[Xr], n, t, e, sa, $[this.layer].el) !== "continue" || Jr($[this.layer].el, n, t);
			}.bind({ layer: d }), 24))), d++;
		} while (t.bubbleScroll && f !== c && (f = Hr(f, !1)));
		ia = l;
	}
}, 30), pa = function(e) {
	var t = e.originalEvent, n = e.putSortable, r = e.dragEl, i = e.activeSortable, a = e.dispatchSortableEvent, o = e.hideGhostForTarget, s = e.unhideGhostForTarget;
	if (t) {
		var c = n || i;
		o();
		var l = t.changedTouches && t.changedTouches.length ? t.changedTouches[0] : t, u = document.elementFromPoint(l.clientX, l.clientY);
		s(), c && !c.el.contains(u) && (a("spill"), this.onSpill({
			dragEl: r,
			putSortable: n
		}));
	}
};
function ma() {}
ma.prototype = {
	startIndex: null,
	dragStart: function(e) {
		this.startIndex = e.oldDraggableIndex;
	},
	onSpill: function(e) {
		var t = e.dragEl, n = e.putSortable;
		this.sortable.captureAnimationState(), n && n.captureAnimationState();
		var r = Lr(this.sortable.el, this.startIndex, this.options);
		r ? this.sortable.el.insertBefore(t, r) : this.sortable.el.appendChild(t), this.sortable.animateAll(), n && n.animateAll();
	},
	drop: pa
}, _r(ma, { pluginName: "revertOnSpill" });
function ha() {}
ha.prototype = {
	onSpill: function(e) {
		var t = e.dragEl, n = e.putSortable || this.sortable;
		n.captureAnimationState(), t.parentNode && t.parentNode.removeChild(t), n.animateAll();
	},
	drop: pa
}, _r(ha, { pluginName: "removeOnSpill" }), Q.mount(new la()), Q.mount(ha, ma);
//#endregion
//#region resources/js/support/filters.ts
var ga = {
	customer: "",
	status: "",
	search: "",
	unbilled: ""
};
function _a(e) {
	return {
		customer: Ta(e.customer),
		status: Ta(e.status),
		search: wa(e.search).slice(0, 200),
		unbilled: e.unbilled === "1" ? "1" : ""
	};
}
function va(e) {
	let t = {};
	for (let n of [
		"customer",
		"status",
		"search",
		"unbilled"
	]) e[n] !== "" && (t[n] = e[n]);
	return t;
}
function ya(e) {
	return e.customer !== "" || e.status !== "" || e.search !== "" || e.unbilled !== "";
}
function ba(e) {
	return [
		e.customer,
		e.status,
		e.search,
		e.unbilled
	].join("|");
}
function xa(e, t) {
	return e.customer === t.customer && e.status === t.status && e.search === t.search && e.unbilled === t.unbilled;
}
function Sa(e) {
	let t = Number(e);
	return e !== "" && Number.isInteger(t) && t > 0 ? t : null;
}
function Ca(e) {
	let t = {}, n = Sa(e.customer);
	n !== null && (t.customer_id = n);
	let r = Sa(e.status);
	return r !== null && (t.status_id = r), e.search !== "" && (t.search = e.search), e.unbilled === "1" && (t.unbilled_only = !0), t;
}
function wa(e) {
	let t = Array.isArray(e) ? e[0] : e;
	return typeof t == "string" ? t.trim() : "";
}
function Ta(e) {
	let t = wa(e);
	return Sa(t) === null ? "" : t;
}
//#endregion
//#region resources/js/components/TripCard.vue?vue&type=script&setup=true&lang.ts
var Ea = { class: "flex items-start justify-between gap-2" }, Da = { class: "text-sm font-medium text-heading" }, Oa = { class: "relative" }, ka = {
	key: 0,
	class: "absolute end-0 z-20 mt-1 w-44 rounded-lg border border-line-default bg-surface p-1 shadow-lg"
}, Aa = ["onClick"], ja = { class: "mt-1 text-sm text-muted" }, Ma = {
	key: 0,
	class: "mt-1 text-xs text-muted"
}, Na = { class: "mt-2 flex items-center justify-between gap-2" }, Pa = { class: "text-xs text-muted" }, Fa = /* @__PURE__ */ c({
	__name: "TripCard",
	props: {
		trip: {},
		statuses: {}
	},
	emits: [
		"open",
		"move",
		"cancel"
	],
	setup(n, { emit: o }) {
		let s = n, c = o, l = _(!1), u = t(() => `${s.trip.from_city ?? "—"} → ${s.trip.to_city ?? "—"}`), f = t(() => {
			let e = s.trip.customers.map((e) => e.name).filter(Boolean);
			return e.length === 0 ? "No customer" : e.length === 1 ? e[0] : `${e[0]} +${e.length - 1} more`;
		}), p = t(() => s.trip.profit >= 0 ? "text-status-green" : "text-status-red");
		function m() {
			l.value = !l.value;
		}
		function h(e) {
			l.value = !1, c("move", s.trip, e);
		}
		function y() {
			l.value = !1, c("cancel", s.trip);
		}
		return (t, o) => (g(), i("article", {
			class: d(["cursor-pointer rounded-lg border border-line-default bg-surface p-3 shadow-sm hover:bg-hover", { "opacity-50": n.trip.cancelled_at }]),
			onClick: o[1] ||= (e) => c("open", n.trip)
		}, [
			a("div", Ea, [a("p", Da, " #" + b(n.trip.trip_no) + " · " + b(u.value), 1), a("div", Oa, [a("button", {
				type: "button",
				class: "rounded px-1 text-muted hover:text-heading",
				"aria-label": "Trip menu",
				onClick: E(m, ["stop"])
			}, " ⋯ "), l.value ? (g(), i("div", ka, [
				a("button", {
					type: "button",
					class: "block w-full rounded px-2 py-1.5 text-start text-sm text-body hover:bg-hover",
					onClick: o[0] ||= (e) => {
						l.value = !1, c("open", n.trip);
					}
				}, " Open trip "),
				o[2] ||= a("p", { class: "px-2 pt-1.5 pb-0.5 text-xs font-medium text-muted" }, "Move to", -1),
				(g(!0), i(e, null, v(n.statuses.filter((e) => e.id !== n.trip.status_id), (e) => (g(), i("button", {
					key: e.id,
					type: "button",
					class: "block w-full rounded px-2 py-1.5 text-start text-sm text-body hover:bg-hover",
					onClick: (t) => h(e.id)
				}, b(e.name), 9, Aa))), 128)),
				n.trip.cancelled_at ? r("", !0) : (g(), i("button", {
					key: 0,
					type: "button",
					class: "mt-1 block w-full rounded px-2 py-1.5 text-start text-sm text-status-red hover:bg-hover",
					onClick: y
				}, " Cancel trip "))
			])) : r("", !0)])]),
			a("p", ja, b(f.value), 1),
			n.trip.lorry_no ? (g(), i("p", Ma, "🚛 " + b(n.trip.lorry_no), 1)) : r("", !0),
			a("div", Na, [a("span", Pa, b(n.trip.lr_count) + " LR" + b(n.trip.lr_count === 1 ? "" : "s") + " · " + b(x(L)(n.trip.revenue)), 1), a("span", { class: d(["text-sm font-semibold", p.value]) }, b(x(L)(n.trip.profit)), 3)])
		], 2));
	}
}), Ia = {
	key: 0,
	class: "py-16 text-center text-sm text-muted"
}, La = {
	key: 1,
	class: "rounded-2xl border border-line-default bg-surface p-12 text-center"
}, Ra = {
	key: 2,
	class: "flex items-stretch gap-4 overflow-x-auto pb-4"
}, za = { class: "flex shrink-0 items-center gap-2 border-b border-line-default bg-surface-secondary/60 px-3 py-2.5" }, Ba = { class: "truncate text-sm font-semibold text-heading" }, Va = { class: "ml-auto shrink-0 rounded-full bg-surface-tertiary px-2 py-0.5 text-xs font-medium text-muted" }, Ha = ["data-status-id"], Ua = ["data-trip-id"], Wa = {
	key: 0,
	class: "rounded-lg border border-dashed border-line-default py-10 text-center text-xs text-muted"
}, Ga = /* @__PURE__ */ c({
	__name: "TripsBoardView",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {},
		filters: {},
		statuses: {},
		loading: { type: Boolean }
	},
	setup(n) {
		let o = n, c = _([]), l = _(!0), u = /* @__PURE__ */ new Map(), d = /* @__PURE__ */ new Map(), h = t(() => o.statuses.map((e) => ({
			status: e,
			cards: c.value.filter((t) => t.status_id === e.id).sort((e, t) => e.board_position - t.board_position)
		}))), y = t(() => !l.value && c.value.length === 0);
		async function x() {
			l.value = !0;
			try {
				c.value = await Ae(o.client, Ca(o.filters));
			} catch (e) {
				o.notify("error", I(e, "Unable to load the board."));
			} finally {
				l.value = !1;
			}
		}
		m(() => {
			x();
		}), C(() => ba(o.filters), () => void x()), p(() => {
			for (let e of u.values()) e.destroy();
			u.clear(), d.clear();
		});
		function S(e) {
			return (t) => {
				if (!(t instanceof HTMLElement)) {
					u.get(e)?.destroy(), u.delete(e), d.delete(e);
					return;
				}
				d.get(e) !== t && (u.get(e)?.destroy(), d.set(e, t), u.set(e, Q.create(t, {
					group: "trips-board",
					animation: 150,
					onEnd: (e) => void T(e)
				})));
			};
		}
		function w(e) {
			let t = e.item, n = e.oldIndex ?? 0;
			t.parentNode?.removeChild(t), e.from.insertBefore(t, e.from.children[n] ?? null);
		}
		async function T(e) {
			let t = Number(e.item.dataset.tripId), n = Number(e.to.dataset.statusId);
			if (w(e), !t || Number.isNaN(n)) return;
			let r = c.value.filter((e) => e.status_id === n && e.id !== t).sort((e, t) => e.board_position - t.board_position), i = r[e.newIndex - 1], a = r[e.newIndex], s = i && a ? (i.board_position + a.board_position) / 2 : a ? a.board_position - .5 : i ? i.board_position + .5 : 1;
			try {
				let e = await Pe(o.client, t, n, s);
				c.value = c.value.map((n) => n.id === t ? e : n);
			} catch (e) {
				o.notify("error", I(e, "Unable to move the trip.")), await x();
			}
		}
		function E(e) {
			o.router?.push(A.trip(e.id));
		}
		async function D(e, t) {
			try {
				let n = await Pe(o.client, e.id, t, null);
				c.value = c.value.map((t) => t.id === e.id ? n : t);
			} catch (e) {
				o.notify("error", I(e, "Unable to move the trip."));
			}
		}
		async function O(e) {
			if (window.confirm("Cancel this trip? It stays on the board, greyed out, and can never be billed.")) try {
				await Fe(o.client, e.id), o.notify("success", "Trip cancelled."), await x();
			} catch (e) {
				o.notify("error", I(e, "Unable to cancel the trip."));
			}
		}
		return (t, o) => (g(), i("div", null, [l.value ? (g(), i("div", Ia, "Loading the board…")) : y.value ? (g(), i("div", La, [...o[0] ||= [
			a("p", { class: "text-4xl" }, "🚛", -1),
			a("h2", { class: "mt-3 text-lg font-semibold text-heading" }, "No trips yet", -1),
			a("p", { class: "mt-1 text-sm text-muted" }, " Your board is empty. Create your first trip by mapping an existing LR Receipt, or by filling a new one. ", -1)
		]])) : (g(), i("div", Ra, [(g(!0), i(e, null, v(h.value, (t) => (g(), i("section", {
			key: t.status.id,
			class: "flex w-72 shrink-0 flex-col overflow-hidden rounded-xl border border-line-default bg-surface shadow-sm"
		}, [
			a("div", {
				class: "h-1 w-full shrink-0",
				style: f({ backgroundColor: t.status.colour ?? "#94a3b8" })
			}, null, 4),
			a("header", za, [
				a("span", {
					class: "inline-block h-2.5 w-2.5 shrink-0 rounded-full",
					style: f({ backgroundColor: t.status.colour ?? "#94a3b8" })
				}, null, 4),
				a("span", Ba, b(t.status.name), 1),
				a("span", Va, b(t.cards.length), 1)
			]),
			a("div", {
				ref_for: !0,
				ref: S(t.status.id),
				"data-status-id": t.status.id,
				class: "min-h-44 flex-1 space-y-2 bg-surface-tertiary/40 p-2"
			}, [(g(!0), i(e, null, v(t.cards, (e) => (g(), i("div", {
				key: e.id,
				"data-trip-id": e.id
			}, [s(Fa, {
				trip: e,
				statuses: n.statuses,
				onOpen: E,
				onMove: D,
				onCancel: O
			}, null, 8, ["trip", "statuses"])], 8, Ua))), 128)), t.cards.length === 0 ? (g(), i("p", Wa, " Drop trips here ")) : r("", !0)], 8, Ha)
		]))), 128))]))]));
	}
}), Ka = {
	key: 0,
	class: "py-16 text-center text-sm text-muted"
}, qa = {
	key: 1,
	class: "rounded-2xl border border-line-default bg-surface p-12 text-center"
}, Ja = {
	key: 2,
	class: "overflow-x-auto rounded-2xl border border-line-default bg-surface"
}, Ya = { class: "w-full text-sm" }, Xa = ["onClick"], Za = { class: "p-3 font-medium text-heading" }, Qa = { class: "p-3 text-body" }, $a = { class: "p-3 text-body" }, eo = { class: "p-3 text-body" }, to = { class: "p-3 text-body" }, no = { class: "p-3 text-end text-body" }, ro = { class: "p-3 text-end text-body" }, io = /* @__PURE__ */ c({
	__name: "TripsListView",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {},
		filters: {},
		statuses: {},
		loading: { type: Boolean }
	},
	setup(n) {
		let r = n, o = _([]), s = _(!0), c = t(() => !s.value && o.value.length === 0), l = t(() => [...o.value].sort((e, t) => t.trip_no - e.trip_no)), u = (e) => r.statuses.find((t) => t.id === e)?.name ?? "—";
		async function f() {
			s.value = !0;
			try {
				o.value = await Ae(r.client, Ca(r.filters));
			} catch (e) {
				r.notify("error", I(e, "Unable to load trips."));
			} finally {
				s.value = !1;
			}
		}
		m(() => {
			f();
		}), C(() => ba(r.filters), () => void f());
		function p(e) {
			r.router?.push(A.trip(e.id));
		}
		return (t, n) => (g(), i("div", null, [s.value ? (g(), i("div", Ka, "Loading trips…")) : c.value ? (g(), i("div", qa, [...n[0] ||= [
			a("p", { class: "text-4xl" }, "🚛", -1),
			a("h2", { class: "mt-3 text-lg font-semibold text-heading" }, "No trips found", -1),
			a("p", { class: "mt-1 text-sm text-muted" }, "Try adjusting your filters, or create a new trip.", -1)
		]])) : (g(), i("div", Ja, [a("table", Ya, [n[1] ||= a("thead", null, [a("tr", { class: "border-b border-line-default text-start text-xs uppercase text-muted" }, [
			a("th", { class: "p-3 text-start font-medium" }, "#"),
			a("th", { class: "p-3 text-start font-medium" }, "Route"),
			a("th", { class: "p-3 text-start font-medium" }, "Customer"),
			a("th", { class: "p-3 text-start font-medium" }, "Lorry"),
			a("th", { class: "p-3 text-start font-medium" }, "Status"),
			a("th", { class: "p-3 text-end font-medium" }, "Revenue"),
			a("th", { class: "p-3 text-end font-medium" }, "Cost"),
			a("th", { class: "p-3 text-end font-medium" }, "Profit")
		])], -1), a("tbody", null, [(g(!0), i(e, null, v(l.value, (e) => (g(), i("tr", {
			key: e.id,
			class: d(["cursor-pointer border-b border-line-light hover:bg-hover", { "opacity-50": e.cancelled_at }]),
			onClick: (t) => p(e)
		}, [
			a("td", Za, "#" + b(e.trip_no), 1),
			a("td", Qa, b(e.from_city ?? "—") + " → " + b(e.to_city ?? "—"), 1),
			a("td", $a, b(e.customers.map((e) => e.name).filter(Boolean).join(", ") || "—"), 1),
			a("td", eo, b(e.lorry_no ?? "—"), 1),
			a("td", to, b(u(e.status_id)), 1),
			a("td", no, b(x(L)(e.revenue)), 1),
			a("td", ro, b(x(L)(e.cost)), 1),
			a("td", { class: d(["p-3 text-end font-semibold", e.profit >= 0 ? "text-status-green" : "text-status-red"]) }, b(x(L)(e.profit)), 3)
		], 10, Xa))), 128))])])]))]));
	}
}), ao = { class: "mb-4 flex items-center justify-between gap-3" }, oo = { class: "flex items-center gap-2" }, so = { class: "text-sm font-semibold text-heading" }, co = { class: "text-sm text-muted" }, lo = {
	key: 0,
	class: "py-16 text-center text-sm text-muted"
}, uo = { class: "grid grid-cols-1 gap-3 md:grid-cols-7" }, fo = { class: "mb-2 flex items-center justify-between" }, po = { class: "text-xs font-medium text-muted" }, mo = {
	key: 0,
	class: "rounded-full bg-surface-tertiary px-2 py-0.5 text-xs text-muted"
}, ho = { class: "flex-1 space-y-2" }, go = ["onClick"], _o = { class: "truncate text-xs font-medium text-heading" }, vo = { class: "mt-0.5 text-xs text-muted" }, yo = {
	key: 0,
	class: "mt-2 border-t border-line-light pt-1 text-xs text-muted"
}, bo = {
	key: 0,
	class: "mt-4"
}, xo = { class: "mb-2 text-xs font-semibold uppercase tracking-wide text-muted" }, So = { class: "flex flex-wrap gap-2" }, Co = ["onClick"], wo = { class: "text-xs font-medium text-heading" }, To = { class: "text-xs text-muted" }, Eo = /* @__PURE__ */ c({
	__name: "TripsWeekView",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {},
		filters: {},
		statuses: {},
		loading: { type: Boolean }
	},
	setup(n) {
		let c = n, l = _([]), u = _(!0), f = _(M(/* @__PURE__ */ new Date())), p = [
			"Sun",
			"Mon",
			"Tue",
			"Wed",
			"Thu",
			"Fri",
			"Sat"
		], h = t(() => p.map((e, t) => {
			let n = new Date(f.value);
			n.setDate(n.getDate() + t);
			let r = N(n), i = l.value.filter((e) => e.pickup_date === r);
			return {
				name: e,
				date: n,
				dateStr: r,
				isToday: r === N(/* @__PURE__ */ new Date()),
				trips: i,
				revenue: i.reduce((e, t) => e + t.revenue, 0)
			};
		})), S = t(() => l.value.filter((e) => !e.pickup_date)), T = t(() => {
			let e = f.value, t = new Date(e);
			t.setDate(t.getDate() + 6);
			let n = (e) => e.toLocaleDateString(void 0, {
				month: "short",
				day: "numeric"
			});
			return `${n(e)} – ${n(t)}`;
		});
		async function E() {
			u.value = !0;
			try {
				l.value = await Ae(c.client, Ca(c.filters));
			} catch (e) {
				c.notify("error", I(e, "Unable to load trips."));
			} finally {
				u.value = !1;
			}
		}
		m(() => {
			E();
		}), C(() => ba(c.filters), () => void E());
		function D() {
			let e = new Date(f.value);
			e.setDate(e.getDate() - 7), f.value = e;
		}
		function O() {
			let e = new Date(f.value);
			e.setDate(e.getDate() + 7), f.value = e;
		}
		function k() {
			f.value = M(/* @__PURE__ */ new Date());
		}
		function j(e) {
			c.router?.push(A.trip(e.id));
		}
		function M(e) {
			let t = new Date(e);
			return t.setHours(0, 0, 0, 0), t.setDate(t.getDate() - t.getDay()), t;
		}
		function N(e) {
			return `${e.getFullYear()}-${String(e.getMonth() + 1).padStart(2, "0")}-${String(e.getDate()).padStart(2, "0")}`;
		}
		return (t, n) => {
			let c = y("BaseButton");
			return g(), i("div", null, [a("div", ao, [a("div", oo, [
				s(c, {
					size: "sm",
					variant: "white",
					onClick: D
				}, {
					default: w(() => [...n[0] ||= [o("←", -1)]]),
					_: 1
				}),
				a("span", so, b(T.value), 1),
				s(c, {
					size: "sm",
					variant: "white",
					onClick: O
				}, {
					default: w(() => [...n[1] ||= [o("→", -1)]]),
					_: 1
				}),
				s(c, {
					size: "sm",
					variant: "white",
					onClick: k
				}, {
					default: w(() => [...n[2] ||= [o("Today", -1)]]),
					_: 1
				})
			]), a("span", co, b(l.value.length) + " trips this week", 1)]), u.value ? (g(), i("div", lo, "Loading the week…")) : (g(), i(e, { key: 1 }, [a("div", uo, [(g(!0), i(e, null, v(h.value, (t) => (g(), i("div", {
				key: t.dateStr,
				class: d(["flex min-h-48 flex-col rounded-xl border bg-surface p-2", t.isToday ? "border-primary-400" : "border-line-default"])
			}, [
				a("div", fo, [a("div", null, [a("p", po, b(t.name), 1), a("p", { class: d(["text-lg font-semibold", t.isToday ? "text-primary-500" : "text-heading"]) }, b(t.date.getDate()), 3)]), t.trips.length > 0 ? (g(), i("span", mo, b(t.trips.length), 1)) : r("", !0)]),
				a("div", ho, [(g(!0), i(e, null, v(t.trips, (e) => (g(), i("div", {
					key: e.id,
					class: "cursor-pointer rounded-lg border border-line-light bg-surface p-2 hover:bg-hover",
					onClick: (t) => j(e)
				}, [a("p", _o, "#" + b(e.trip_no) + " · " + b(e.from_city ?? "—") + " → " + b(e.to_city ?? "—"), 1), a("p", vo, b(e.lorry_no ?? "No lorry") + " · " + b(x(L)(e.revenue)), 1)], 8, go))), 128))]),
				t.trips.length > 0 ? (g(), i("p", yo, b(x(L)(t.revenue)), 1)) : r("", !0)
			], 2))), 128))]), S.value.length > 0 ? (g(), i("div", bo, [a("p", xo, " Unscheduled (" + b(S.value.length) + ") ", 1), a("div", So, [(g(!0), i(e, null, v(S.value, (e) => (g(), i("div", {
				key: e.id,
				class: "cursor-pointer rounded-lg border border-line-light bg-surface p-2 hover:bg-hover",
				onClick: (t) => j(e)
			}, [a("p", wo, "#" + b(e.trip_no), 1), a("p", To, b(e.from_city ?? "—") + " → " + b(e.to_city ?? "—"), 1)], 8, Co))), 128))])])) : r("", !0)], 64))]);
		};
	}
}), Do = { class: "mt-4 flex flex-wrap items-end gap-3" }, Oo = { class: "min-w-44 flex-1" }, ko = { class: "min-w-44 flex-1" }, Ao = { class: "min-w-44 flex-1" }, jo = { class: "flex items-center gap-1.5 pb-2.5 text-sm text-body" }, Mo = ["checked"], No = 350, Po = /* @__PURE__ */ c({
	__name: "TripFilters",
	props: {
		modelValue: {},
		statuses: {}
	},
	emits: ["update:modelValue"],
	setup(e, { emit: n }) {
		let c = e, l = n, u = _(c.modelValue.search), d, f = t(() => [{
			id: "",
			label: "All statuses"
		}, ...c.statuses.map((e) => ({
			id: String(e.id),
			label: e.name
		}))]), m = t({
			get: () => v(f.value, c.modelValue.status),
			set: (e) => b({ status: e?.id ?? "" })
		}), h = t(() => ya(c.modelValue));
		C(() => c.modelValue.search, (e) => {
			e !== u.value && (u.value = e);
		}), C(u, (e) => {
			clearTimeout(d), d = setTimeout(() => b({ search: e.trim() }), No);
		}), p(() => clearTimeout(d));
		function v(e, t) {
			return e.find((e) => e.id === t) ?? e[0];
		}
		function b(e) {
			l("update:modelValue", {
				...c.modelValue,
				...e
			});
		}
		function x() {
			b({ unbilled: c.modelValue.unbilled === "1" ? "" : "1" });
		}
		function S() {
			u.value = "", l("update:modelValue", { ...ga });
		}
		return (t, n) => {
			let c = y("BaseSelectInput"), l = y("BaseCustomerSelectInput"), d = y("BaseInput");
			return g(), i("div", Do, [
				a("label", Oo, [n[3] ||= a("span", { class: "mb-1 block text-xs font-medium text-muted" }, "Status", -1), s(c, {
					modelValue: m.value,
					"onUpdate:modelValue": n[0] ||= (e) => m.value = e,
					options: f.value,
					"label-key": "label"
				}, null, 8, ["modelValue", "options"])]),
				a("label", ko, [n[4] ||= a("span", { class: "mb-1 block text-xs font-medium text-muted" }, "Customer", -1), s(l, {
					"model-value": e.modelValue.customer ? Number(e.modelValue.customer) : null,
					"can-deselect": "",
					placeholder: "All customers",
					"onUpdate:modelValue": n[1] ||= (e) => b({ customer: e ? String(e) : "" })
				}, null, 8, ["model-value"])]),
				a("label", Ao, [n[5] ||= a("span", { class: "mb-1 block text-xs font-medium text-muted" }, "Search", -1), s(d, {
					modelValue: u.value,
					"onUpdate:modelValue": n[2] ||= (e) => u.value = e,
					type: "text",
					name: "search",
					autocomplete: "off",
					placeholder: "Search route, lorry, goods..."
				}, null, 8, ["modelValue"])]),
				a("label", jo, [a("input", {
					type: "checkbox",
					class: "accent-primary-600",
					checked: e.modelValue.unbilled === "1",
					onChange: x
				}, null, 40, Mo), n[6] ||= o(" Unbilled only ", -1)]),
				h.value ? (g(), i("button", {
					key: 0,
					type: "button",
					class: "pb-2.5 text-sm font-medium text-primary-500 hover:underline",
					onClick: S
				}, " Clear ")) : r("", !0)
			]);
		};
	}
}), Fo = {
	class: "inline-flex overflow-hidden rounded-lg border border-line-default",
	"aria-label": "Trip views"
}, Io = ["aria-current", "onClick"], Lo = { class: "max-sm:sr-only" }, Ro = /* @__PURE__ */ c({
	__name: "TripViewSwitcher",
	props: {
		active: {},
		query: {}
	},
	emits: ["select"],
	setup(t, { emit: n }) {
		let r = t, o = n, c = [
			{
				id: "board",
				name: j.board,
				label: "Board",
				icon: "ViewColumnsIcon"
			},
			{
				id: "list",
				name: j.list,
				label: "List",
				icon: "ListBulletIcon"
			},
			{
				id: "week",
				name: j.week,
				label: "Week",
				icon: "CalendarDaysIcon"
			}
		];
		function l(e) {
			return r.active === e.name || e.id === "board" && r.active === j.trips;
		}
		function u(e) {
			l(e) || o("select", {
				name: e.name,
				query: r.query
			});
		}
		return (t, n) => {
			let r = y("BaseIcon");
			return g(), i("nav", Fo, [(g(), i(e, null, v(c, (e) => a("button", {
				key: e.id,
				type: "button",
				class: d(["flex items-center gap-1.5 border-e border-line-default px-3 py-1.5 text-sm font-medium last:border-e-0", l(e) ? "bg-primary-50 text-primary-500" : "bg-surface text-muted hover:bg-hover hover:text-heading"]),
				"aria-current": l(e) ? "page" : void 0,
				onClick: (t) => u(e)
			}, [s(r, {
				name: e.icon,
				class: "h-4 w-4"
			}, null, 8, ["name"]), a("span", Lo, b(e.label), 1)], 10, Io)), 64))]);
		};
	}
}), zo = { class: "flex flex-wrap items-center justify-end gap-3" }, Bo = /* @__PURE__ */ c({
	__name: "TripsPage",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {}
	},
	setup(e) {
		let r = e, i = r.router, c = _([]), l = _(!0), u = t(() => r.router.currentRoute.value), f = t(() => _a(u.value.query)), p = t(() => va(f.value)), h = t(() => String(u.value.name ?? ""));
		C(h, (e) => v(e)), m(() => {
			v(h.value), b();
		});
		function v(e) {
			e === j.trips && i.replace({
				name: j.board,
				query: p.value
			});
		}
		async function b() {
			l.value = !0;
			try {
				c.value = await je(r.client);
			} catch (e) {
				r.notify("error", I(e, "Unable to load statuses."));
			} finally {
				l.value = !1;
			}
		}
		function S(e) {
			xa(e, f.value) || i.replace({
				name: h.value === j.trips ? j.board : h.value,
				query: va(e)
			});
		}
		function T(e) {
			i.push(e);
		}
		function E() {
			i.push(A.new);
		}
		return (e, t) => {
			let r = y("BaseBreadcrumbItem"), i = y("BaseBreadcrumb"), u = y("BaseIcon"), m = y("BaseButton"), _ = y("router-link"), v = y("BasePageHeader"), b = y("router-view"), C = y("BasePage");
			return g(), n(C, null, {
				default: w(() => [
					s(v, { title: "Trips" }, {
						actions: w(() => [a("div", zo, [
							s(Ro, {
								active: h.value,
								query: p.value,
								onSelect: T
							}, null, 8, ["active", "query"]),
							s(_, { to: x(A).reports }, {
								default: w(() => [s(m, { variant: "white" }, {
									left: w((e) => [s(u, {
										name: "ChartBarIcon",
										class: d(e.class)
									}, null, 8, ["class"])]),
									default: w(() => [t[0] ||= o(" Reports ", -1)]),
									_: 1
								})]),
								_: 1
							}, 8, ["to"]),
							s(m, {
								variant: "primary",
								onClick: E
							}, {
								left: w((e) => [s(u, {
									name: "PlusIcon",
									class: d(e.class)
								}, null, 8, ["class"])]),
								default: w(() => [t[1] ||= o(" New Trip ", -1)]),
								_: 1
							})
						])]),
						default: w(() => [s(i, null, {
							default: w(() => [s(r, {
								title: "Dashboard",
								to: "/admin/dashboard"
							}), s(r, {
								title: "Trips",
								to: "#",
								active: ""
							})]),
							_: 1
						})]),
						_: 1
					}),
					s(Po, {
						"model-value": f.value,
						statuses: c.value,
						"onUpdate:modelValue": S
					}, null, 8, ["model-value", "statuses"]),
					s(b, {
						filters: f.value,
						statuses: c.value,
						loading: l.value
					}, null, 8, [
						"filters",
						"statuses",
						"loading"
					])
				]),
				_: 1
			});
		};
	}
}), Vo = `${O}:view-trip`;
function Ho(e) {
	e.registerPage({
		id: "trips",
		module: O,
		path: "",
		component: M(e, Bo),
		meta: {
			ability: Vo,
			title: "trips.title"
		},
		children: [
			{
				id: "board",
				module: O,
				path: "",
				component: M(e, Ga),
				meta: {
					ability: Vo,
					title: "trips.title"
				}
			},
			{
				id: "list",
				module: O,
				path: "list",
				component: M(e, io),
				meta: {
					ability: Vo,
					title: "trips.title"
				}
			},
			{
				id: "week",
				module: O,
				path: "week",
				component: M(e, Eo),
				meta: {
					ability: Vo,
					title: "trips.title"
				}
			}
		]
	}), e.registerPage({
		id: "new",
		module: O,
		path: "new",
		component: M(e, yt),
		meta: {
			ability: Vo,
			title: "trips.title"
		}
	}), e.registerPage({
		id: "trip",
		module: O,
		path: ":id(\\d+)",
		component: M(e, fr),
		meta: {
			ability: Vo,
			title: "trips.title"
		}
	});
}
//#endregion
//#region resources/js/init.ts
window.InvoiceShelf.booting((e, t, n) => {
	n.addMessages(D), Ho(n), ke(n);
});
//#endregion
