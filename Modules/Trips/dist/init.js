const { Fragment: e, computed: t, createBlock: n, createCommentVNode: r, createElementBlock: i, createElementVNode: a, createStaticVNode: o, createTextVNode: s, createVNode: c, defineComponent: l, getCurrentInstance: u, h: d, normalizeClass: f, normalizeStyle: p, onBeforeUnmount: m, onMounted: h, onUnmounted: g, openBlock: _, ref: v, renderList: y, resolveComponent: b, toDisplayString: x, unref: S, vModelSelect: C, watch: w, withCtx: T, withDirectives: E, withModifiers: D } = window.__invoiceshelf_vue;
//#region resources/js/messages.ts
var O = { en: { trips: {
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
} } }, k = "trips", A = `/admin/modules/${k}`, j = {
	board: A,
	list: `${A}/list`,
	week: `${A}/week`,
	new: `${A}/new`,
	trip: (e) => `${A}/${e}`,
	reports: `${A}/reports`,
	invoiceView: (e) => `/admin/invoices/${e}/view`,
	invoiceCreate: (e) => `/admin/invoices/create?template=${e}`
}, M = {
	trips: `extension.page.${k}.trips`,
	board: `extension.page.${k}.trips.board`,
	list: `extension.page.${k}.trips.list`,
	week: `extension.page.${k}.trips.week`,
	trip: `extension.page.${k}.trip`,
	reports: `extension.page.${k}.reports`
};
function N(e, t) {
	return l({ setup: (n, { attrs: r }) => () => d(t, {
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
function ee() {
	return u()?.appContext.config.globalProperties.$t ?? ((e) => e);
}
//#endregion
//#region resources/js/support/useTripReportDownload.ts
function te(e, t) {
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
var ne = (e) => e.getFullYear() + "-" + String(e.getMonth() + 1).padStart(2, "0") + "-" + String(e.getDate()).padStart(2, "0");
function re(e) {
	let t = new Date(e);
	return t.setHours(0, 0, 0, 0), t;
}
function ie(e) {
	let t = new Date(e);
	return t.setHours(23, 59, 59, 999), t;
}
function ae(e) {
	let t = re(e), n = (t.getDay() + 6) % 7;
	return t.setDate(t.getDate() - n), t;
}
function oe(e) {
	let t = ae(e), n = new Date(t);
	return n.setDate(t.getDate() + 6), ie(n);
}
function se(e) {
	return re(new Date(e.getFullYear(), e.getMonth(), 1));
}
function ce(e) {
	return ie(new Date(e.getFullYear(), e.getMonth() + 1, 0));
}
function le(e) {
	let t = Math.floor(e.getMonth() / 3) * 3;
	return re(new Date(e.getFullYear(), t, 1));
}
function ue(e) {
	let t = Math.floor(e.getMonth() / 3) * 3;
	return ie(new Date(e.getFullYear(), t + 3, 0));
}
function de(e) {
	return re(new Date(e.getFullYear(), 0, 1));
}
function fe(e) {
	return ie(new Date(e.getFullYear(), 12, 0));
}
var pe = {
	isoWeek: ae,
	month: se,
	quarter: le,
	year: de
}, me = {
	isoWeek: oe,
	month: ce,
	quarter: ue,
	year: fe
};
function he(e, t) {
	return {
		from: ne(pe[e](t)),
		to: ne(me[e](t))
	};
}
function ge(e, t) {
	let n = new Date(t);
	return e === "isoWeek" ? n.setDate(n.getDate() - 7) : e === "month" ? n.setMonth(n.getMonth() - 1) : e === "quarter" ? n.setMonth(n.getMonth() - 3) : n.setFullYear(n.getFullYear() - 1), {
		from: ne(pe[e](n)),
		to: ne(me[e](n))
	};
}
function _e() {
	return he("month", /* @__PURE__ */ new Date());
}
function ve(e) {
	let t = /* @__PURE__ */ new Date();
	switch (e) {
		case "This Week": return he("isoWeek", t);
		case "This Month": return he("month", t);
		case "This Quarter": return he("quarter", t);
		case "This Year": return he("year", t);
		case "Previous Week": return ge("isoWeek", t);
		case "Previous Month": return ge("month", t);
		case "Previous Quarter": return ge("quarter", t);
		case "Previous Year": return ge("year", t);
		default: return {
			from: ne(t),
			to: ne(t)
		};
	}
}
//#endregion
//#region resources/js/support/period.ts
function ye(e) {
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
		range: () => ve(t)
	}));
}
function be(e) {
	let t = e.range?.();
	return {
		preset: e.key,
		from: t?.from ?? null,
		to: t?.to ?? null
	};
}
//#endregion
//#region resources/js/components/TripReportPdfPane.vue?vue&type=script&setup=true&lang.ts
var xe = {
	key: 0,
	class: "flex items-center justify-center w-full h-screen border border-line-default border-solid rounded bg-surface"
}, Se = {
	key: 1,
	class: "flex flex-col items-center justify-center gap-4 w-full h-screen border border-line-default border-solid rounded bg-surface"
}, Ce = ["src"], we = /* @__PURE__ */ l({
	__name: "TripReportPdfPane",
	props: {
		client: { type: [Function, Object] },
		path: {}
	},
	setup(e, { expose: t }) {
		let n = e, r = v("idle"), o = v(null), l = null, u = null, d = 0;
		function f() {
			o.value && URL.revokeObjectURL(o.value), o.value = null, l = null, u = null;
		}
		async function p(e) {
			let t = ++d;
			f(), r.value = "loading";
			try {
				let i = await n.client.get(e, { responseType: "blob" });
				if (t !== d) return;
				let a = i.data;
				if (a.type.startsWith("text/html") || a.type.startsWith("application/json")) throw Error("Server returned a message instead of a PDF");
				l = a, u = e, o.value = URL.createObjectURL(a), r.value = "ready";
			} catch {
				t === d && (r.value = "error");
			}
		}
		function h() {
			n.path && p(n.path);
		}
		async function g(e) {
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
		return w(() => n.path, (e) => {
			if (e) {
				p(e);
				return;
			}
			d++, f(), r.value = "idle";
		}, { immediate: !0 }), m(f), t({ view: g }), (e, t) => {
			let n = b("BaseSpinner"), l = b("BaseIcon"), u = b("BaseButton");
			return _(), i("div", null, [r.value === "loading" || r.value === "idle" ? (_(), i("div", xe, [c(n, { class: "w-8 h-8 text-primary-400" })])) : r.value === "error" ? (_(), i("div", Se, [
				c(l, {
					name: "ExclamationCircleIcon",
					class: "w-12 h-12 text-muted"
				}),
				t[1] ||= a("p", { class: "text-sm text-muted" }, "Unable to load the report.", -1),
				c(u, {
					variant: "primary-outline",
					size: "sm",
					onClick: h
				}, {
					default: T(() => [...t[0] ||= [s("Retry", -1)]]),
					_: 1
				})
			])) : (_(), i("iframe", {
				key: 2,
				src: o.value ?? void 0,
				title: "Trip Report PDF",
				class: "w-full h-screen border border-line-default border-solid rounded bg-surface"
			}, null, 8, Ce))]);
		};
	}
}), Te = { class: "grid gap-8 md:grid-cols-12 pt-10" }, Ee = { class: "col-span-8 md:col-span-4" }, De = { class: "hidden mt-6 md:block" }, Oe = { class: "col-span-8" }, ke = /* @__PURE__ */ l({
	__name: "TripReportsPage",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {}
	},
	setup(e) {
		let r = e, i = ye(ee()), o = v(be(i[2])), l = [
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
		], u = v("customer"), d = v(null), p = v(null), m = _e(), g = v(m.from), y = v(m.to), x = v(""), C = v(""), E = v(""), O = v(""), k = t(() => d.value), A = t(() => {
			let e = "/api/v1/trips/reports/pdf?from_date=" + g.value + "&to_date=" + y.value + "&type=" + u.value;
			return x.value.trim() && (e += "&customer=" + encodeURIComponent(x.value.trim())), C.value.trim() && (e += "&lr_no=" + encodeURIComponent(C.value.trim())), E.value.trim() && (e += "&invoice_no=" + encodeURIComponent(E.value.trim())), O.value.trim() && (e += "&supplier=" + encodeURIComponent(O.value.trim())), e;
		}), M = te(r.client, () => (d.value = A.value, d.value));
		h(() => {
			d.value = A.value;
		}), w(o, (e) => {
			e.from && e.to && (g.value = e.from, y.value = e.to);
		}), w([
			g,
			y,
			u,
			x,
			C,
			E,
			O
		], () => {
			d.value = A.value;
		});
		function N() {
			return d.value = A.value, !0;
		}
		function ne() {
			N(), p.value?.view(d.value);
		}
		function re() {
			r.router.push(j.board);
		}
		return (t, r) => {
			let d = b("BaseBreadcrumbItem"), m = b("BaseBreadcrumb"), h = b("BaseIcon"), g = b("BaseButton"), v = b("BasePageHeader"), y = b("BasePeriodPicker"), w = b("BaseInputGroup"), A = b("BaseMultiselect"), ee = b("BaseInput"), te = b("BasePage");
			return _(), n(te, null, {
				default: T(() => [c(v, { title: "Trip Reports" }, {
					actions: T(() => [c(g, {
						variant: "white",
						onClick: re
					}, {
						left: T((e) => [c(h, {
							name: "ArrowLeftIcon",
							class: f(e.class)
						}, null, 8, ["class"])]),
						default: T(() => [r[6] ||= s(" Back to Trips ", -1)]),
						_: 1
					}), c(g, {
						variant: "primary",
						class: "ms-4",
						onClick: S(M)
					}, {
						left: T((e) => [c(h, {
							name: "ArrowDownTrayIcon",
							class: f(e.class)
						}, null, 8, ["class"])]),
						default: T(() => [r[7] ||= s(" Download PDF ", -1)]),
						_: 1
					}, 8, ["onClick"])]),
					default: T(() => [c(m, null, {
						default: T(() => [
							c(d, {
								title: "Dashboard",
								to: "/admin/dashboard"
							}),
							c(d, {
								title: "Trips",
								to: S(j).board
							}, null, 8, ["to"]),
							c(d, {
								title: "Reports",
								to: "#",
								active: ""
							})
						]),
						_: 1
					})]),
					_: 1
				}), a("div", Te, [a("div", Ee, [
					c(w, {
						label: "Date Range",
						class: "mb-6"
					}, {
						default: T(() => [c(y, {
							modelValue: o.value,
							"onUpdate:modelValue": r[0] ||= (e) => o.value = e,
							presets: S(i),
							block: "",
							position: "bottom-start"
						}, null, 8, ["modelValue", "presets"])]),
						_: 1
					}),
					c(w, {
						label: "Report Type",
						class: "col-span-12 md:col-span-8"
					}, {
						default: T(() => [c(A, {
							modelValue: u.value,
							"onUpdate:modelValue": [r[1] ||= (e) => u.value = e, N],
							options: l,
							placeholder: "Select report type",
							class: "mt-1"
						}, null, 8, ["modelValue"])]),
						_: 1
					}),
					c(w, {
						label: "Customer Name",
						class: "col-span-12 md:col-span-8 mt-4"
					}, {
						default: T(() => [c(ee, {
							modelValue: x.value,
							"onUpdate:modelValue": [r[2] ||= (e) => x.value = e, N],
							placeholder: "Type customer name..."
						}, null, 8, ["modelValue"])]),
						_: 1
					}),
					c(w, {
						label: "LR No",
						class: "col-span-12 md:col-span-8"
					}, {
						default: T(() => [c(ee, {
							modelValue: C.value,
							"onUpdate:modelValue": [r[3] ||= (e) => C.value = e, N],
							placeholder: "Type LR number..."
						}, null, 8, ["modelValue"])]),
						_: 1
					}),
					c(w, {
						label: "Invoice No",
						class: "col-span-12 md:col-span-8"
					}, {
						default: T(() => [c(ee, {
							modelValue: E.value,
							"onUpdate:modelValue": [r[4] ||= (e) => E.value = e, N],
							placeholder: "Type invoice number..."
						}, null, 8, ["modelValue"])]),
						_: 1
					}),
					c(w, {
						label: "Supplier Name",
						class: "col-span-12 md:col-span-8"
					}, {
						default: T(() => [c(ee, {
							modelValue: O.value,
							"onUpdate:modelValue": [r[5] ||= (e) => O.value = e, N],
							placeholder: "Type supplier name..."
						}, null, 8, ["modelValue"])]),
						_: 1
					}),
					a("div", De, [c(g, {
						variant: "primary",
						class: "justify-center w-full",
						type: "submit",
						onClick: D(N, ["prevent"])
					}, {
						left: T((e) => [c(h, {
							name: "ArrowPathIcon",
							class: f(e.class)
						}, null, 8, ["class"])]),
						default: T(() => [r[8] ||= s(" Update Report ", -1)]),
						_: 1
					})])
				]), a("div", Oe, [c(we, {
					ref_key: "pdfPane",
					ref: p,
					client: e.client,
					path: k.value,
					class: "hidden md:block"
				}, null, 8, ["client", "path"]), a("button", {
					type: "button",
					class: "flex items-center justify-center w-full gap-2 px-5 text-sm font-medium transition-colors rounded-lg h-11 md:hidden bg-btn-primary text-on-primary hover:bg-btn-primary-hover",
					onClick: ne
				}, [c(h, {
					name: "DocumentTextIcon",
					class: "w-5 h-5"
				}), r[9] ||= s(" View PDF ", -1)])])])]),
				_: 1
			});
		};
	}
}), Ae = `${k}:view-trip`;
function je(e) {
	e.registerPage({
		id: "reports",
		module: k,
		path: "reports",
		component: N(e, ke),
		meta: {
			ability: Ae,
			title: "Trip Reports"
		}
	});
}
//#endregion
//#region resources/js/api/trips.ts
var P = "/api/v1/trips";
async function Me(e, t = {}) {
	let { data: n } = await e.get(P, { params: t });
	return n.data;
}
async function Ne(e) {
	let { data: t } = await e.get(`${P}/statuses`);
	return t.data;
}
async function Pe(e, t) {
	let { data: n } = await e.get(`${P}/${t}`);
	return n.data;
}
async function Fe(e, t, n) {
	let { data: r } = await e.post(`${P}/from-lrs`, {
		invoice_ids: t,
		...n
	});
	return r.data;
}
async function Ie(e, t, n, r) {
	let { data: i } = await e.post(`${P}/${t}/move`, {
		status_id: n,
		position: r
	});
	return i.data;
}
async function Le(e, t) {
	let { data: n } = await e.post(`${P}/${t}/cancel`);
	return n.data;
}
async function Re(e, t, n) {
	let { data: r } = await e.patch(`${P}/${t}/status`, { status_id: n });
	return r.data;
}
async function ze(e, t = "lr", n = null, r = null, i = !1) {
	let { data: a } = await e.get(`${P}/unlinked-lrs`, { params: {
		type: t,
		customer_id: n,
		trip_id: r,
		all: i ? 1 : void 0
	} });
	return a.data;
}
async function Be(e, t, n, r) {
	let { data: i } = await e.post(`${P}/${t}/receipts`, {
		invoice_id: n,
		type: r
	});
	return i.data;
}
async function Ve(e, t) {
	let { data: n } = await e.post(`${P}/${t}/fetch-lorry-receipt`);
	return n.data;
}
async function He(e, t) {
	let { data: n } = await e.post(`${P}/${t}/fetch-invoice-receipt`);
	return n.data;
}
async function Ue(e, t, n, r) {
	let i = new FormData();
	i.append("file", n), i.append("category", r);
	let { data: a } = await e.post(`${P}/${t}/pod`, i, { headers: { "Content-Type": "multipart/form-data" } });
	return a.data;
}
async function We(e, t, n) {
	let { data: r } = await e.delete(`${P}/${t}/pod/${n}`);
	return r.data;
}
async function Ge(e, t, n) {
	let { data: r } = await e.get(`${P}/${t}/pod/${n}`, { responseType: "blob" });
	return r;
}
async function Ke(e, t, n) {
	let { data: r } = await e.delete(`${P}/${t}/receipts/${n}`);
	return r.data;
}
//#endregion
//#region resources/js/support/http.ts
function F(e, t) {
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
function I(e) {
	return ((e ?? 0) / 100).toLocaleString(void 0, {
		minimumFractionDigits: 2,
		maximumFractionDigits: 2
	});
}
function qe() {
	let e = /* @__PURE__ */ new Date();
	return `${e.getFullYear()}-${String(e.getMonth() + 1).padStart(2, "0")}-${String(e.getDate()).padStart(2, "0")}`;
}
//#endregion
//#region resources/js/pages/TripCreatePage.vue?vue&type=script&setup=true&lang.ts
var Je = { class: "pt-5 mt-5 border-t border-line-light" }, Ye = { class: "flex flex-wrap items-center justify-between gap-3 mb-4" }, Xe = { class: "flex gap-2" }, Ze = {
	key: 0,
	class: "p-4 text-sm rounded-lg bg-surface-secondary text-muted"
}, Qe = {
	key: 1,
	class: "p-4 text-sm rounded-lg bg-surface-secondary text-muted",
	role: "status"
}, $e = {
	key: 2,
	class: "space-y-3"
}, et = { class: "flex h-[38px] items-center rounded-lg bg-surface-tertiary px-3 text-sm font-medium text-heading" }, tt = { class: "justify-self-end" }, nt = { class: "flex flex-wrap justify-end gap-x-8 gap-y-2 pt-4 mt-4 text-sm border-t border-line-light" }, rt = { class: "text-muted" }, it = { class: "font-semibold text-heading" }, at = { class: "text-heading font-semibold" }, ot = /* @__PURE__ */ l({
	__name: "TripCreatePage",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {}
	},
	setup(o) {
		let l = o, u = v(qe()), d = v(""), p = v(null), m = v(""), h = v([]), g = v([]), C = v(!1), E = v(!1), O = t(() => h.value);
		async function k(e) {
			if (g.value = [], !e) {
				h.value = [];
				return;
			}
			C.value = !0;
			try {
				h.value = await ze(l.client, "lr", e);
			} catch (e) {
				l.notify("error", F(e, "Unable to load LR receipts.")), h.value = [];
			} finally {
				C.value = !1;
			}
		}
		w(p, (e, t) => {
			t !== void 0 && e !== t && k(e);
		});
		function A(e) {
			if (!e) return;
			let t = typeof e == "object" && e ? e.id : e, n = Number(t);
			if (!(!n || isNaN(n))) return h.value.find((e) => e.id === n);
		}
		function M(e) {
			return A(e)?.amount ?? 0;
		}
		function N(e) {
			let t = e ? Number(typeof e == "object" ? e.id : e) : null, n = g.value.map((e) => {
				let t = typeof e.receipt_id == "object" && e.receipt_id !== null ? e.receipt_id.id : e.receipt_id;
				return t ? Number(t) : null;
			}).filter((e) => e !== null && e !== t);
			return h.value.filter((e) => !n.includes(e.id)).map((e) => ({
				id: e.id,
				label: `${e.invoice_number} · ${e.from_city ?? "—"} → ${e.to_city ?? "—"} (${I(e.amount)})`
			}));
		}
		let ee = t(() => g.value.map((e) => {
			let t = typeof e.receipt_id == "object" && e.receipt_id !== null ? e.receipt_id.id : e.receipt_id;
			return t ? Number(t) : null;
		}).filter((e) => e !== null)), te = t(() => g.value.map((e) => A(e.receipt_id)).filter((e) => e !== void 0)), ne = t(() => te.value.reduce((e, t) => e + t.amount, 0));
		function re() {
			g.value.push({ receipt_id: null });
		}
		function ie(e) {
			g.value.splice(e, 1);
		}
		function ae() {
			g.value = [...h.value].sort((e, t) => (e.invoice_date ?? "9999-12-31").localeCompare(t.invoice_date ?? "9999-12-31") || e.id - t.id).map((e) => ({ receipt_id: e.id }));
		}
		async function oe() {
			if (!p.value) {
				l.notify("warning", "Please select a customer first.");
				return;
			}
			if (ee.value.length === 0) {
				l.notify("warning", "Please allocate at least one LR receipt to this trip.");
				return;
			}
			E.value = !0;
			try {
				let e = await Fe(l.client, ee.value, {
					pickup_date: u.value,
					notes: m.value
				});
				l.notify("success", `Trip #${e.trip_no} created successfully.`), l.router?.push(j.trip(e.id));
			} catch (e) {
				l.notify("error", F(e, "Unable to create the trip."));
			} finally {
				E.value = !1;
			}
		}
		return (t, o) => {
			let l = b("BaseBreadcrumbItem"), h = b("BaseBreadcrumb"), v = b("BaseIcon"), w = b("BaseButton"), A = b("BasePageHeader"), ee = b("BaseDatePicker"), se = b("BaseInputGroup"), ce = b("BaseInput"), le = b("BaseCustomerSelectInput"), ue = b("BaseInputGrid"), de = b("BaseMultiselect"), fe = b("BaseTextarea"), pe = b("BaseCard"), me = b("BasePage");
			return _(), n(me, { class: "relative trip-create" }, {
				default: T(() => [a("form", {
					class: "flex flex-col gap-4 md:gap-5",
					onSubmit: D(oe, ["prevent"])
				}, [c(A, {
					title: "New Trip",
					"phone-actions": "bar"
				}, {
					actions: T(() => [c(w, {
						loading: E.value,
						disabled: E.value,
						variant: "primary",
						type: "submit"
					}, {
						left: T((e) => [E.value ? r("", !0) : (_(), n(v, {
							key: 0,
							name: "ArrowDownOnSquareIcon",
							class: f(e.class)
						}, null, 8, ["class"]))]),
						default: T(() => [o[4] ||= s(" Save Trip ", -1)]),
						_: 1
					}, 8, ["loading", "disabled"])]),
					default: T(() => [c(h, null, {
						default: T(() => [
							c(l, {
								title: "Home",
								to: "/admin/dashboard"
							}),
							c(l, {
								title: "Trips",
								to: S(j).board
							}, null, 8, ["to"]),
							c(l, {
								title: "New Trip",
								to: "#",
								active: ""
							})
						]),
						_: 1
					})]),
					_: 1
				}), c(pe, { "container-class": "p-4 md:p-5" }, {
					default: T(() => [
						c(ue, null, {
							default: T(() => [
								c(se, {
									label: "Date",
									required: ""
								}, {
									default: T(() => [c(ee, {
										modelValue: u.value,
										"onUpdate:modelValue": o[0] ||= (e) => u.value = e,
										"calendar-button": !0,
										"calendar-button-icon": "calendar"
									}, null, 8, ["modelValue"])]),
									_: 1
								}),
								c(se, {
									label: "Trip Number",
									required: ""
								}, {
									default: T(() => [c(ce, {
										modelValue: d.value,
										"onUpdate:modelValue": o[1] ||= (e) => d.value = e,
										placeholder: "Auto-generated"
									}, null, 8, ["modelValue"])]),
									_: 1
								}),
								c(se, {
									label: "Customer",
									required: ""
								}, {
									default: T(() => [c(le, {
										modelValue: p.value,
										"onUpdate:modelValue": [o[2] ||= (e) => p.value = e, k],
										placeholder: "Type or click to select",
										"show-action": ""
									}, null, 8, ["modelValue"])]),
									_: 1
								})
							]),
							_: 1
						}),
						a("section", Je, [
							a("div", Ye, [o[7] ||= a("div", null, [a("h2", { class: "font-semibold text-section text-heading" }, "LR Allocations"), a("p", { class: "mt-1 text-sm text-muted" }, "Allocate one or more open LR receipts to this trip.")], -1), a("div", Xe, [c(w, {
								type: "button",
								size: "sm",
								variant: "primary-outline",
								disabled: !O.value.length,
								onClick: ae
							}, {
								default: T(() => [...o[5] ||= [s(" Allocate Oldest First ", -1)]]),
								_: 1
							}, 8, ["disabled"]), c(w, {
								type: "button",
								size: "sm",
								variant: "primary-outline",
								disabled: !p.value || !N(null).length,
								onClick: re
							}, {
								left: T((e) => [c(v, {
									name: "PlusIcon",
									class: f(e.class)
								}, null, 8, ["class"])]),
								default: T(() => [o[6] ||= s(" Add Allocation ", -1)]),
								_: 1
							}, 8, ["disabled"])])]),
							p.value ? g.value.length ? (_(), i("div", $e, [(_(!0), i(e, null, y(g.value, (e, t) => (_(), i("div", {
								key: `${e.receipt_id}-${t}`,
								class: "grid items-end gap-3 p-3 rounded-lg bg-surface-secondary md:grid-cols-[minmax(0,1fr)_12rem_auto]"
							}, [
								c(se, { label: "LR Receipt" }, {
									default: T(() => [c(de, {
										modelValue: e.receipt_id,
										"onUpdate:modelValue": (t) => e.receipt_id = t,
										"value-prop": "id",
										"track-by": "id",
										label: "label",
										options: N(e.receipt_id),
										loading: C.value,
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
								c(se, { label: "Amount" }, {
									default: T(() => [a("div", et, x(e.receipt_id ? S(I)(M(e.receipt_id)) : "—"), 1)]),
									_: 2
								}, 1024),
								a("div", tt, [c(w, {
									type: "button",
									size: "sm",
									variant: "gray",
									"aria-label": "Remove allocation",
									onClick: (e) => ie(t)
								}, {
									default: T(() => [c(v, { name: "TrashIcon" })]),
									_: 1
								}, 8, ["onClick"])])
							]))), 128))])) : (_(), i("div", Qe, x(!C.value && !O.value.length ? "No open LR receipts for this customer." : "No allocations added yet. Click \"+ Add Allocation\" or \"Allocate Oldest First\"."), 1)) : (_(), i("div", Ze, " Select a customer before allocating this trip. ")),
							a("div", nt, [a("span", rt, [o[8] ||= s("Total LRs: ", -1), a("span", it, x(te.value.length), 1)]), a("span", at, "Allocated: " + x(S(I)(ne.value)), 1)])
						]),
						c(se, {
							label: "Notes",
							class: "mt-5"
						}, {
							default: T(() => [c(fe, {
								modelValue: m.value,
								"onUpdate:modelValue": o[3] ||= (e) => m.value = e,
								rows: 4,
								placeholder: "Add any notes about this trip..."
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					]),
					_: 1
				})], 32)]),
				_: 1
			});
		};
	}
}), st = { class: "space-y-3" }, ct = {
	key: 1,
	class: "flex items-center justify-between text-xs text-muted"
}, lt = {
	key: 2,
	class: "flex items-center justify-between text-xs text-muted"
}, ut = {
	key: 3,
	class: "py-8 text-center text-sm text-muted"
}, dt = {
	key: 4,
	class: "rounded-lg bg-surface-secondary p-4 text-sm text-muted"
}, ft = { key: 0 }, pt = { key: 1 }, mt = { key: 2 }, ht = {
	key: 5,
	class: "max-h-72 space-y-1 overflow-y-auto"
}, gt = ["onClick"], _t = ["checked", "onChange"], vt = { class: "min-w-0 flex-1" }, yt = { class: "block font-medium text-heading" }, bt = { class: "block truncate text-xs text-muted" }, xt = { class: "text-sm font-semibold text-heading" }, St = {
	key: 6,
	class: "text-sm text-muted"
}, Ct = {
	key: 7,
	class: "text-sm text-alert-warning-text"
}, wt = { class: "flex justify-end gap-2" }, Tt = /* @__PURE__ */ l({
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
	setup(o, { emit: l }) {
		let u = o, d = l, p = v([]), m = v([]), g = v(null), C = v(!1), E = v(!1), O = t(() => u.multiple ?? !0);
		async function k() {
			E.value = !0;
			try {
				p.value = await ze(u.client, u.type ?? "lr", g.value, u.tripId ?? null, C.value), m.value = [];
			} catch (e) {
				u.notify("error", F(e, "Unable to load the receipts."));
			} finally {
				E.value = !1;
			}
		}
		h(() => {
			u.show && k();
		}), w(() => u.show, (e) => {
			e && (C.value = !1, k());
		}), w([g, C], () => {
			k();
		});
		function A(e) {
			if (!O.value) {
				m.value = [e];
				return;
			}
			m.value.includes(e) ? m.value = m.value.filter((t) => t !== e) : m.value = [...m.value, e];
		}
		let j = t(() => p.value.filter((e) => m.value.includes(e.id)).reduce((e, t) => e + t.amount, 0)), M = t(() => {
			let e = p.value.filter((e) => m.value.includes(e.id));
			return new Set(e.map((e) => `${e.from_city}→${e.to_city}`)).size > 1;
		});
		function N() {
			m.value.length !== 0 && d("select", [...m.value]);
		}
		return (t, l) => {
			let u = b("BaseSelectInput"), h = b("BaseInputGroup"), v = b("BaseButton"), w = b("BaseModal");
			return _(), n(w, {
				show: o.show,
				onClose: l[6] ||= (e) => d("close")
			}, {
				header: T(() => [a("span", null, x(o.type === "lorry" ? "Map Lorry Receipt" : "Map LR Receipts"), 1)]),
				default: T(() => [a("div", st, [
					o.customers && o.customers.length > 0 ? (_(), n(h, {
						key: 0,
						label: "Customer"
					}, {
						default: T(() => [c(u, {
							modelValue: g.value,
							"onUpdate:modelValue": l[0] ||= (e) => g.value = e,
							options: [{
								id: null,
								label: "All customers"
							}, ...o.customers.map((e) => ({
								id: e.id,
								label: e.name
							}))],
							"value-prop": "id",
							"label-key": "label"
						}, null, 8, ["modelValue", "options"])]),
						_: 1
					})) : r("", !0),
					o.type === "lorry" && o.tripId ? (_(), i("div", ct, [a("span", null, x(C.value ? "Showing all unlinked lorry receipts" : "Showing lorry receipts matching this trip’s LR numbers"), 1), a("button", {
						type: "button",
						class: "font-medium text-primary-600 hover:underline",
						onClick: l[1] ||= (e) => C.value = !C.value
					}, x(C.value ? "Show matching only" : "Show all unlinked"), 1)])) : o.type === "lr" && o.tripId ? (_(), i("div", lt, [a("span", null, x(C.value ? "Showing all unlinked LR receipts" : "Showing unlinked LR receipts for customers on this trip"), 1), a("button", {
						type: "button",
						class: "font-medium text-primary-600 hover:underline",
						onClick: l[2] ||= (e) => C.value = !C.value
					}, x(C.value ? "Show trip customers only" : "Browse all customers"), 1)])) : r("", !0),
					E.value ? (_(), i("div", ut, "Loading…")) : p.value.length === 0 ? (_(), i("div", dt, [o.type === "lorry" && o.tripId && !C.value ? (_(), i("p", ft, [l[7] ||= s(" No lorry receipt found with matching bilty numbers for this trip's LRs. ", -1), a("button", {
						type: "button",
						class: "mt-1 block font-medium text-primary-600 hover:underline",
						onClick: l[3] ||= (e) => C.value = !0
					}, " Browse all unlinked lorry receipts instead → ")])) : o.type === "lr" && o.tripId && !C.value ? (_(), i("p", pt, [l[8] ||= s(" No more unassigned LR receipts found for the customers on this trip. ", -1), a("button", {
						type: "button",
						class: "mt-1 block font-medium text-primary-600 hover:underline",
						onClick: l[4] ||= (e) => C.value = !0
					}, " Browse all unlinked LR receipts instead → ")])) : (_(), i("p", mt, " No unlinked receipts. Create one first. "))])) : (_(), i("ul", ht, [(_(!0), i(e, null, y(p.value, (e) => (_(), i("li", { key: e.id }, [a("label", {
						class: f(["flex cursor-pointer items-center gap-3 rounded-lg border border-line-default bg-surface p-2.5 text-sm hover:bg-hover", { "border-primary-500": m.value.includes(e.id) }]),
						onClick: D((t) => A(e.id), ["prevent"])
					}, [
						a("input", {
							type: "checkbox",
							class: "accent-primary-600",
							checked: m.value.includes(e.id),
							onChange: (t) => A(e.id)
						}, null, 40, _t),
						a("span", vt, [a("span", yt, x(e.invoice_number) + " · " + x(e.from_city ?? "—") + " → " + x(e.to_city ?? "—"), 1), a("span", bt, x(e.customer_name ?? "No customer") + " · " + x(e.goods ?? ""), 1)]),
						a("span", xt, x(S(I)(e.amount)), 1)
					], 10, gt)]))), 128))])),
					m.value.length > 0 ? (_(), i("p", St, " Selected: " + x(m.value.length) + " · " + x(S(I)(j.value)), 1)) : r("", !0),
					M.value ? (_(), i("p", Ct, " ⚠ Different routes selected — same vehicle? ")) : r("", !0)
				])]),
				footer: T(() => [a("div", wt, [c(v, {
					variant: "primary-outline",
					size: "sm",
					onClick: l[5] ||= (e) => d("close")
				}, {
					default: T(() => [...l[9] ||= [s("Cancel", -1)]]),
					_: 1
				}), c(v, {
					variant: "primary",
					size: "sm",
					disabled: m.value.length === 0,
					onClick: N
				}, {
					default: T(() => [s(x(O.value ? `Link ${m.value.length}` : "Link"), 1)]),
					_: 1
				}, 8, ["disabled"])])]),
				_: 1
			}, 8, ["show"]);
		};
	}
}), Et = {
	key: 0,
	class: "fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/60 p-4 print:p-0 print:bg-white"
}, Dt = { class: "relative w-full max-w-2xl rounded-xl border border-line-default bg-surface p-6 shadow-2xl print:border-0 print:shadow-none print:max-w-none print:w-full print:p-8" }, Ot = { class: "mb-4 flex items-center justify-between border-b border-line-light pb-3 print:hidden" }, kt = { class: "flex items-center gap-2" }, At = { class: "text-xs text-muted" }, jt = { class: "flex items-center gap-2" }, Mt = { class: "voucher-box border-2 border-slate-800 p-5 font-sans text-slate-900 print:border-2 print:border-slate-800" }, Nt = { class: "mt-3 grid grid-cols-2 gap-4 text-xs border-b border-slate-300 pb-3" }, Pt = { class: "font-bold text-slate-900" }, Ft = { class: "mt-1" }, It = { class: "mt-1" }, Lt = { class: "font-semibold uppercase text-indigo-700" }, Rt = { class: "text-right" }, zt = { class: "font-bold text-slate-900" }, Bt = { class: "mt-1" }, Vt = { class: "font-bold uppercase text-slate-900" }, Ht = { class: "mt-1" }, Ut = { class: "mt-4 space-y-2 text-xs" }, Wt = { class: "flex items-start justify-between border-b border-dashed border-slate-200 py-1.5" }, Gt = { class: "font-bold text-slate-900 text-sm" }, Kt = { class: "flex items-start justify-between border-b border-dashed border-slate-200 py-1.5" }, qt = { class: "font-medium text-slate-800" }, Jt = {
	key: 0,
	class: "flex items-start justify-between border-b border-dashed border-slate-200 py-1.5"
}, Yt = { class: "font-mono font-medium text-slate-800" }, Xt = {
	key: 1,
	class: "flex items-start justify-between border-b border-dashed border-slate-200 py-1.5"
}, Zt = { class: "text-slate-800" }, Qt = { class: "my-5 flex items-center justify-between rounded border-2 border-slate-900 bg-slate-50 px-4 py-3" }, $t = { class: "text-2xl font-extrabold text-slate-900" }, en = /* @__PURE__ */ ((e, t) => {
	let n = e.__vccOpts || e;
	for (let [e, r] of t) n[e] = r;
	return n;
})(/* @__PURE__ */ l({
	__name: "TripPaymentVoucherModal",
	props: {
		show: { type: Boolean },
		payment: {},
		trip: {}
	},
	emits: ["close"],
	setup(e, { emit: n }) {
		let c = e, l = n, u = t(() => c.payment ? c.payment.type === "advance" ? "Advance Payment" : "Final Settlement Payment" : "Payment");
		function d() {
			window.print();
		}
		return (t, n) => e.show && e.payment && e.trip ? (_(), i("div", Et, [a("div", Dt, [a("div", Ot, [a("div", kt, [n[1] ||= a("span", { class: "rounded bg-primary-50 px-2 py-0.5 text-xs font-semibold text-primary-700" }, " Payment Voucher ", -1), a("span", At, "Voucher #" + x(e.payment.number ?? "—"), 1)]), a("div", jt, [a("button", {
			type: "button",
			class: "inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-primary-700",
			onClick: d
		}, [...n[2] ||= [a("svg", {
			class: "h-4 w-4",
			fill: "none",
			viewBox: "0 0 24 24",
			stroke: "currentColor"
		}, [a("path", {
			"stroke-linecap": "round",
			"stroke-linejoin": "round",
			"stroke-width": "2",
			d: "M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
		})], -1), s(" Print Voucher ", -1)]]), a("button", {
			type: "button",
			class: "rounded-lg p-1.5 text-muted hover:bg-surface-secondary hover:text-heading",
			"aria-label": "Close",
			onClick: n[0] ||= (e) => l("close")
		}, [...n[3] ||= [a("svg", {
			class: "h-5 w-5",
			fill: "none",
			viewBox: "0 0 24 24",
			stroke: "currentColor"
		}, [a("path", {
			"stroke-linecap": "round",
			"stroke-linejoin": "round",
			"stroke-width": "2",
			d: "M6 18L18 6M6 6l12 12"
		})], -1)]])])]), a("div", Mt, [
			n[19] ||= a("div", { class: "border-b-2 border-slate-800 pb-3 text-center" }, [a("h2", { class: "text-xl font-bold tracking-wide uppercase text-slate-900" }, "PAYMENT VOUCHER"), a("p", { class: "text-xs uppercase tracking-wider text-slate-600 font-medium" }, "Truck Hire & Freight Disbursement Memo")], -1),
			a("div", Nt, [a("div", null, [
				a("p", null, [
					n[4] ||= a("span", { class: "font-semibold text-slate-700" }, "Voucher No:", -1),
					n[5] ||= s(),
					a("span", Pt, x(e.payment.number ?? "—"), 1)
				]),
				a("p", Ft, [n[6] ||= a("span", { class: "font-semibold text-slate-700" }, "Payment Date:", -1), s(" " + x(e.payment.date ?? "—"), 1)]),
				a("p", It, [
					n[7] ||= a("span", { class: "font-semibold text-slate-700" }, "Payment Nature:", -1),
					n[8] ||= s(),
					a("span", Lt, x(u.value), 1)
				])
			]), a("div", Rt, [
				a("p", null, [
					n[9] ||= a("span", { class: "font-semibold text-slate-700" }, "Trip No:", -1),
					n[10] ||= s(),
					a("span", zt, "#" + x(e.trip.trip_no), 1)
				]),
				a("p", Bt, [
					n[11] ||= a("span", { class: "font-semibold text-slate-700" }, "Truck / Lorry No:", -1),
					n[12] ||= s(),
					a("span", Vt, x(e.payment.lorry_no || e.trip.lorry_no || "—"), 1)
				]),
				a("p", Ht, [n[13] ||= a("span", { class: "font-semibold text-slate-700" }, "Route:", -1), s(" " + x(e.trip.from_city ?? "—") + " → " + x(e.trip.to_city ?? "—"), 1)])
			])]),
			a("div", Ut, [
				a("div", Wt, [n[14] ||= a("span", { class: "font-semibold text-slate-700" }, "Paid To (Beneficiary):", -1), a("span", Gt, x(e.payment.pay_to ?? "Vehicle Owner / Supplier"), 1)]),
				a("div", Kt, [n[15] ||= a("span", { class: "font-semibold text-slate-700" }, "Payment Mode:", -1), a("span", qt, x(e.payment.method ?? "Bank Transfer / NEFT"), 1)]),
				e.payment.reference ? (_(), i("div", Jt, [n[16] ||= a("span", { class: "font-semibold text-slate-700" }, "Reference / UTR / Cheque No:", -1), a("span", Yt, x(e.payment.reference), 1)])) : r("", !0),
				e.payment.notes ? (_(), i("div", Xt, [n[17] ||= a("span", { class: "font-semibold text-slate-700" }, "Particulars / Remarks:", -1), a("span", Zt, x(e.payment.notes), 1)])) : r("", !0)
			]),
			a("div", Qt, [n[18] ||= a("span", { class: "text-xs font-bold uppercase tracking-wider text-slate-700" }, "Amount Paid", -1), a("span", $t, x(S(I)(e.payment.amount)), 1)]),
			n[20] ||= o("<div class=\"mt-8 grid grid-cols-3 gap-4 pt-8 text-center text-xs\" data-v-c761222a><div class=\"border-t border-slate-400 pt-1\" data-v-c761222a><p class=\"font-medium text-slate-700\" data-v-c761222a>Prepared By</p></div><div class=\"border-t border-slate-400 pt-1\" data-v-c761222a><p class=\"font-medium text-slate-700\" data-v-c761222a>Checked / Verified</p></div><div class=\"border-t border-slate-400 pt-1\" data-v-c761222a><p class=\"font-medium text-slate-700\" data-v-c761222a>Receiver&#39;s Signature</p></div></div>", 1)
		])])])) : r("", !0);
	}
}), [["__scopeId", "data-v-c761222a"]]), tn = { class: "min-h-screen bg-surface-secondary" }, nn = { class: "mx-auto max-w-5xl p-4 lg:p-6" }, rn = {
	key: 0,
	class: "py-16 text-center text-sm text-muted"
}, an = { class: "mb-4 flex flex-wrap items-center justify-between gap-3" }, on = { class: "text-xl font-semibold text-heading" }, sn = {
	key: 0,
	class: "inline-flex items-center rounded-md bg-primary-50 px-2 py-0.5 text-sm font-semibold text-primary-700 ring-1 ring-inset ring-primary-700/20 align-middle ml-1.5",
	title: "Consolidated Multi-LR Load"
}, cn = {
	key: 1,
	class: "text-base font-normal text-muted"
}, ln = { class: "flex items-center gap-2" }, un = {
	key: 0,
	class: "rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-600 ring-1 ring-inset ring-emerald-500/20"
}, dn = {
	key: 1,
	class: "rounded-full bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-600 ring-1 ring-inset ring-amber-500/20"
}, fn = {
	key: 2,
	class: "rounded-full bg-slate-500/10 px-2.5 py-1 text-xs font-medium text-slate-500 ring-1 ring-inset ring-slate-500/20"
}, pn = {
	key: 3,
	class: "rounded-full bg-red-600 px-3 py-1 text-xs font-semibold text-white"
}, mn = ["disabled"], hn = ["value"], gn = {
	key: 0,
	class: "mb-4 rounded-lg bg-alert-error-bg p-3 text-sm text-alert-error-text"
}, _n = { class: "space-y-4" }, vn = { class: "space-y-2" }, yn = { class: "min-w-0" }, bn = { class: "font-medium text-heading" }, xn = { class: "truncate text-xs text-muted" }, Sn = { class: "flex shrink-0 gap-1" }, Cn = {
	key: 0,
	class: "text-sm text-muted"
}, wn = { class: "flex gap-2 pt-1" }, Tn = { class: "mt-4 border-t border-line-light pt-3" }, En = { class: "mb-2 flex items-center justify-between" }, Dn = { class: "flex items-center gap-2" }, On = { class: "min-w-0" }, kn = { class: "font-medium text-heading" }, An = { class: "truncate text-xs text-muted" }, jn = { class: "flex shrink-0 gap-1" }, Mn = {
	key: 0,
	class: "text-sm text-muted"
}, Nn = {
	key: 0,
	class: "text-xs text-muted"
}, Pn = { class: "mt-4 border-t border-line-light pt-3" }, Fn = { class: "mb-2 flex items-center justify-between" }, In = { class: "flex items-center gap-2" }, Ln = { class: "min-w-0" }, Rn = { class: "font-medium text-heading" }, zn = { class: "truncate text-xs text-muted" }, Bn = { class: "flex shrink-0 gap-1" }, Vn = {
	key: 0,
	class: "text-sm text-muted"
}, Hn = {
	key: 0,
	class: "text-xs text-muted"
}, Un = { class: "mt-4 border-t border-line-light pt-3" }, Wn = { class: "mb-2 text-sm font-semibold text-heading" }, Gn = { class: "grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" }, Kn = { class: "mb-1.5 text-xs font-medium text-muted" }, qn = {
	key: 0,
	class: "group relative flex h-28 items-center justify-center overflow-hidden rounded-lg border border-line-default bg-surface-secondary"
}, Jn = ["src", "alt"], Yn = {
	key: 1,
	class: "flex flex-col items-center gap-1 text-subtle"
}, Xn = {
	key: 2,
	class: "flex items-center justify-center text-subtle"
}, Zn = { class: "absolute inset-0 flex items-center justify-center gap-1 bg-black/40 opacity-0 transition-opacity group-hover:opacity-100" }, Qn = ["onClick"], $n = ["disabled", "onClick"], er = {
	key: 2,
	class: "mt-1 flex items-center justify-between gap-1"
}, tr = { class: "min-w-0 truncate text-xs text-muted" }, nr = ["onClick"], rr = { class: "mt-4 border-t border-line-light pt-3" }, ir = { class: "grid grid-cols-2 gap-3 text-center sm:grid-cols-4" }, ar = { class: "rounded-xl border border-line-light bg-surface-secondary p-3" }, or = { class: "mt-1 text-lg font-bold text-heading" }, sr = { class: "mt-1 flex flex-wrap items-center justify-center gap-1 text-[11px]" }, cr = { class: "font-medium text-status-green" }, lr = {
	key: 0,
	class: "font-medium text-indigo-600"
}, ur = {
	key: 1,
	class: "font-medium text-status-red"
}, dr = { class: "rounded-xl border border-line-light bg-surface-secondary p-3" }, fr = { class: "mt-1 text-lg font-bold text-heading" }, pr = { class: "mt-1 text-[11px] text-muted" }, mr = { key: 0 }, hr = { class: "rounded-xl border border-line-light bg-surface-secondary p-3" }, gr = { class: "mt-1 text-lg font-bold text-heading" }, _r = { class: "rounded-xl border border-line-light bg-surface-secondary p-3" }, vr = { class: "mt-1 text-[11px] font-medium text-muted" }, yr = {
	key: 0,
	class: "mt-4"
}, br = { class: "flex items-center justify-between gap-2" }, xr = { class: "min-w-0" }, Sr = { class: "font-medium text-heading" }, Cr = { class: "truncate text-xs text-muted" }, wr = {
	key: 0,
	class: "mt-2.5 space-y-1.5 border-t border-line-light pt-2"
}, Tr = { class: "flex items-center gap-2" }, Er = { class: "font-medium text-heading" }, Dr = { class: "text-muted" }, Or = { class: "flex items-center gap-3" }, kr = { class: "text-right" }, Ar = { class: "font-bold text-heading" }, jr = {
	key: 0,
	class: "block text-[10px] text-muted"
}, Mr = ["onClick"], Nr = {
	key: 1,
	class: "mt-4"
}, Pr = { class: "flex items-center justify-between gap-2" }, Fr = { class: "min-w-0" }, Ir = { class: "font-medium text-heading" }, Lr = {
	key: 0,
	class: "text-xs font-normal text-muted"
}, Rr = { class: "truncate text-xs text-muted" }, zr = { key: 0 }, Br = {
	key: 0,
	class: "mt-1 text-xs text-muted"
}, Vr = { class: "font-semibold text-heading" }, Hr = {
	key: 1,
	class: "mt-2.5 space-y-1.5 border-t border-line-light pt-2"
}, Ur = { class: "flex items-center gap-2" }, Wr = { class: "text-muted" }, Gr = { key: 0 }, Kr = { class: "flex items-center gap-3" }, qr = { class: "font-bold text-heading" }, Jr = ["onClick"], Yr = { class: "mt-3 flex items-center justify-between rounded-lg border border-line-light bg-surface-secondary p-2.5 text-xs" }, Xr = { class: "text-muted" }, Zr = { class: "text-heading" }, Qr = /* @__PURE__ */ l({
	__name: "TripDetailPage",
	props: {
		id: {},
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {}
	},
	setup(o) {
		let l = o, u = v(null), d = v(!0), m = v([]), D = v(!1), O = v(!1), k = v(!1), A = v(!1), M = v(!1), N = v(null), ee = v(null), te = v(""), ne = t(() => Number(l.id)), re = t({
			get: () => u.value?.status.id ?? null,
			set: (e) => {
				e !== null && ye(e);
			}
		}), ie = t(() => u.value?.receipts.filter((e) => e.type === "lr") ?? []), ae = t(() => u.value?.receipts.filter((e) => e.type === "lorry") ?? []), oe = t(() => u.value?.receipts.filter((e) => e.type === "invoice") ?? []), se = v(!1), ce = v(null);
		function le(e) {
			ce.value = e, se.value = !0;
		}
		function ue(e) {
			e && window.open("/payments/pdf/" + e, "_blank");
		}
		let de = [
			{
				role: "consignment",
				label: "Consignment & Proof of Delivery",
				slots: [{
					key: "pod",
					label: "Signed Delivery Copy / POD"
				}]
			},
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
		function fe(e) {
			return u.value?.documents.find((t) => t.category === e) ?? null;
		}
		let pe = v({});
		function me() {
			for (let e of Object.values(pe.value)) URL.revokeObjectURL(e);
			pe.value = {};
		}
		async function he() {
			if (u.value) {
				me();
				for (let e of u.value.documents) try {
					let t = await Ge(l.client, u.value.id, e.id);
					pe.value[e.id] = URL.createObjectURL(t);
				} catch {}
			}
		}
		function ge(e) {
			let t = pe.value[e];
			t && window.open(t, "_blank", "noopener");
		}
		w(() => u.value?.documents, () => {
			he();
		});
		async function _e() {
			d.value = !0;
			try {
				u.value = await Pe(l.client, ne.value);
			} catch (e) {
				l.notify("error", F(e, "Unable to load the trip."));
			} finally {
				d.value = !1;
			}
		}
		async function ve() {
			try {
				m.value = await Ne(l.client);
			} catch {}
		}
		async function ye(e) {
			if (!(!u.value || D.value)) {
				D.value = !0;
				try {
					u.value = await Re(l.client, u.value.id, e), l.notify("success", "Status updated.");
				} catch (e) {
					l.notify("error", F(e, "Unable to change the status."));
				} finally {
					D.value = !1;
				}
			}
		}
		function be() {
			document.hidden || _e();
		}
		h(() => {
			_e(), ve(), document.addEventListener("visibilitychange", be);
		}), g(() => {
			me(), document.removeEventListener("visibilitychange", be);
		});
		async function xe() {
			if (!(!u.value || !window.confirm("Cancel this trip?"))) try {
				u.value = await Le(l.client, u.value.id), l.notify("success", "Trip cancelled.");
			} catch (e) {
				l.notify("error", F(e, "Unable to cancel the trip."));
			}
		}
		async function Se(e) {
			if (u.value) {
				O.value = !1;
				try {
					for (let t of e) u.value = await Be(l.client, u.value.id, t, "lr");
					l.notify("success", "LR receipt linked.");
				} catch (e) {
					l.notify("error", F(e, "Unable to link the receipt."));
				}
			}
		}
		async function Ce(e) {
			if (!(!u.value || e.length === 0)) {
				k.value = !1;
				try {
					for (let t of e) u.value = await Be(l.client, u.value.id, t, "lorry");
					l.notify("success", "Lorry receipt linked.");
				} catch (e) {
					l.notify("error", F(e, "Unable to link the receipt."));
				}
			}
		}
		async function we() {
			if (u.value) {
				M.value = !0;
				try {
					u.value = await Ve(l.client, u.value.id), l.notify("success", "Lorry receipt(s) fetched and linked.");
				} catch (e) {
					l.notify("error", F(e, "No lorry receipt found with matching bilty numbers for these LRs."));
				} finally {
					M.value = !1;
				}
			}
		}
		async function Te(e) {
			if (u.value) try {
				u.value = await Ke(l.client, u.value.id, e), l.notify("success", "Receipt unlinked.");
			} catch (e) {
				l.notify("error", F(e, "Unable to unlink the receipt."));
			}
		}
		async function Ee() {
			if (u.value) {
				A.value = !0;
				try {
					u.value = await He(l.client, u.value.id), l.notify("success", "Invoice receipt fetched and linked.");
				} catch (e) {
					l.notify("error", F(e, "No invoice receipt found for these LR numbers."));
				} finally {
					A.value = !1;
				}
			}
		}
		function De(e) {
			te.value = e, ee.value?.click();
		}
		async function Oe(e) {
			if (!u.value) return;
			let t = e.target, n = t.files;
			if (!n || n.length === 0) return;
			let r = te.value;
			N.value = r;
			try {
				u.value = await Ue(l.client, u.value.id, n[0], r), l.notify("success", "Document uploaded.");
			} catch (e) {
				l.notify("error", F(e, "Unable to upload the document."));
			} finally {
				N.value = null, t.value = "";
			}
		}
		async function ke(e) {
			if (u.value) try {
				u.value = await We(l.client, u.value.id, e), l.notify("success", "Document removed.");
			} catch (e) {
				l.notify("error", F(e, "Unable to remove the document."));
			}
		}
		function Ae(e) {
			l.router?.push(j.invoiceView(e));
		}
		function je() {
			l.router?.push(j.board);
		}
		return (t, l) => {
			let h = b("BaseButton"), g = b("BaseSettingCard");
			return _(), i("div", tn, [
				a("div", nn, [d.value ? (_(), i("div", rn, "Loading the trip…")) : u.value ? (_(), i(e, { key: 1 }, [
					a("div", an, [a("div", null, [a("button", {
						type: "button",
						class: "text-sm text-muted hover:text-heading",
						onClick: je
					}, " ← Trips "), a("h1", on, [
						s(" Trip #" + x(u.value.trip_no) + " ", 1),
						u.value.lr_numbers && u.value.lr_numbers.length > 1 ? (_(), i("span", sn, " LR: " + x(u.value.lr_numbers.join(", ")), 1)) : u.value.lr_numbers && u.value.lr_numbers.length === 1 && String(u.value.trip_no) !== String(u.value.lr_numbers[0]) ? (_(), i("span", cn, " [LR: " + x(u.value.lr_numbers[0]) + "] ", 1)) : r("", !0),
						s(" · " + x(u.value.from_city ?? "—") + " → " + x(u.value.to_city ?? "—"), 1)
					])]), a("div", ln, [
						u.value.money?.payment_status === "PAID" ? (_(), i("span", un, " ● Payment Received ")) : u.value.money?.payment_status === "PARTIALLY_PAID" ? (_(), i("span", dn, " ◐ Partially Paid ")) : u.value.money?.payment_status ? (_(), i("span", fn, " ○ Payment Pending ")) : r("", !0),
						u.value.cancelled_at ? (_(), i("span", pn, " Cancelled ")) : m.value.length > 0 ? E((_(), i("select", {
							key: 4,
							"onUpdate:modelValue": l[0] ||= (e) => re.value = e,
							disabled: D.value,
							class: "cursor-pointer rounded-full border-0 px-3 py-1 text-xs font-semibold text-white outline-none",
							style: p({ backgroundColor: u.value.status.colour ?? "#94a3b8" })
						}, [(_(!0), i(e, null, y(m.value, (e) => (_(), i("option", {
							key: e.id,
							value: e.id,
							class: "bg-surface text-body"
						}, x(e.name), 9, hn))), 128))], 12, mn)), [[C, re.value]]) : (_(), i("span", {
							key: 5,
							class: "rounded-full px-3 py-1 text-xs font-semibold text-white",
							style: p({ backgroundColor: u.value.status.colour ?? "#94a3b8" })
						}, x(u.value.status.name), 5)),
						u.value.cancelled_at ? r("", !0) : (_(), n(h, {
							key: 6,
							size: "sm",
							variant: "danger",
							onClick: xe
						}, {
							default: T(() => [...l[6] ||= [s(" Cancel Trip ", -1)]]),
							_: 1
						}))
					])]),
					u.value.cancelled_at ? (_(), i("div", gn, " This trip is cancelled. ")) : r("", !0),
					a("div", _n, [c(g, {
						title: "Documents",
						description: "LR receipts, lorry receipt, invoice receipt and trip documents for this trip."
					}, {
						default: T(() => [
							a("div", vn, [
								(_(!0), i(e, null, y(ie.value, (e) => (_(), i("div", {
									key: e.id,
									class: "flex items-center justify-between gap-2 rounded-lg border border-line-light p-2.5 text-sm"
								}, [a("div", yn, [a("p", bn, "📄 " + x(e.invoice_number), 1), a("p", xn, x(e.customer_name ?? "No customer") + " · " + x(S(I)(e.amount)), 1)]), a("div", Sn, [c(h, {
									size: "sm",
									variant: "primary-outline",
									onClick: (t) => Ae(e.invoice_id)
								}, {
									default: T(() => [...l[7] ||= [s(" View ", -1)]]),
									_: 1
								}, 8, ["onClick"]), e.billed_invoice_id ? r("", !0) : (_(), n(h, {
									key: 0,
									size: "sm",
									variant: "danger-outline",
									onClick: (t) => Te(e.id)
								}, {
									default: T(() => [...l[8] ||= [s(" Unlink ", -1)]]),
									_: 1
								}, 8, ["onClick"]))])]))), 128)),
								ie.value.length === 0 ? (_(), i("p", Cn, "No LR receipts linked yet.")) : r("", !0),
								a("div", wn, [c(h, {
									size: "sm",
									variant: "primary-outline",
									onClick: l[1] ||= (e) => O.value = !0
								}, {
									default: T(() => [...l[9] ||= [s(" + Link LR ", -1)]]),
									_: 1
								})])
							]),
							a("div", Tn, [
								a("div", En, [l[11] ||= a("p", { class: "text-xs font-semibold uppercase tracking-wide text-muted" }, "Lorry Receipt", -1), a("div", Dn, [c(h, {
									size: "sm",
									variant: "primary-outline",
									disabled: M.value || ie.value.length === 0,
									onClick: we
								}, {
									default: T(() => [s(x(M.value ? "Fetching…" : "Fetch Lorry Receipt"), 1)]),
									_: 1
								}, 8, ["disabled"]), c(h, {
									size: "sm",
									variant: "primary-outline",
									onClick: l[2] ||= (e) => k.value = !0
								}, {
									default: T(() => [...l[10] ||= [s(" + Link Lorry Receipt ", -1)]]),
									_: 1
								})])]),
								(_(!0), i(e, null, y(ae.value, (e) => (_(), i("div", {
									key: e.id,
									class: "mb-2 flex items-center justify-between gap-2 rounded-lg border border-line-light p-2.5 text-sm"
								}, [a("div", On, [a("p", kn, "🚛 " + x(e.invoice_number), 1), a("p", An, x(e.customer_name ?? "No customer") + " · " + x(S(I)(e.amount)), 1)]), a("div", jn, [c(h, {
									size: "sm",
									variant: "primary-outline",
									onClick: (t) => Ae(e.invoice_id)
								}, {
									default: T(() => [...l[12] ||= [s(" View ", -1)]]),
									_: 1
								}, 8, ["onClick"]), e.billed_invoice_id ? r("", !0) : (_(), n(h, {
									key: 0,
									size: "sm",
									variant: "danger-outline",
									onClick: (t) => Te(e.id)
								}, {
									default: T(() => [...l[13] ||= [s(" Unlink ", -1)]]),
									_: 1
								}, 8, ["onClick"]))])]))), 128)),
								ae.value.length === 0 ? (_(), i("p", Mn, [l[14] ||= s(" No lorry receipt linked yet. ", -1), ie.value.length === 0 ? (_(), i("span", Nn, " (Link an LR first)")) : r("", !0)])) : r("", !0)
							]),
							a("div", Pn, [
								a("div", Fn, [l[15] ||= a("p", { class: "text-xs font-semibold uppercase tracking-wide text-muted" }, "Invoice Receipt", -1), a("div", In, [c(h, {
									size: "sm",
									variant: "primary-outline",
									disabled: A.value || ie.value.length === 0,
									onClick: Ee
								}, {
									default: T(() => [s(x(A.value ? "Fetching…" : "Fetch Invoice Receipt"), 1)]),
									_: 1
								}, 8, ["disabled"])])]),
								(_(!0), i(e, null, y(oe.value, (e) => (_(), i("div", {
									key: e.id,
									class: "mb-2 flex items-center justify-between gap-2 rounded-lg border border-line-light p-2.5 text-sm"
								}, [a("div", Ln, [a("p", Rn, "🧾 " + x(e.invoice_number), 1), a("p", zn, x(e.customer_name ?? "No customer") + " · " + x(S(I)(e.amount)), 1)]), a("div", Bn, [c(h, {
									size: "sm",
									variant: "primary-outline",
									onClick: (t) => Ae(e.invoice_id)
								}, {
									default: T(() => [...l[16] ||= [s(" View ", -1)]]),
									_: 1
								}, 8, ["onClick"]), e.billed_invoice_id ? r("", !0) : (_(), n(h, {
									key: 0,
									size: "sm",
									variant: "danger-outline",
									onClick: (t) => Te(e.id)
								}, {
									default: T(() => [...l[17] ||= [s(" Unlink ", -1)]]),
									_: 1
								}, 8, ["onClick"]))])]))), 128)),
								oe.value.length === 0 ? (_(), i("p", Vn, [l[18] ||= s(" No invoice receipt linked yet. ", -1), ie.value.length === 0 ? (_(), i("span", Hn, " (Link an LR first)")) : r("", !0)])) : r("", !0)
							]),
							a("div", Un, [
								l[27] ||= a("p", { class: "mb-3 text-xs font-semibold uppercase tracking-wide text-muted" }, "Trip Documents", -1),
								a("input", {
									ref_key: "podFileInput",
									ref: ee,
									type: "file",
									class: "hidden",
									accept: "image/*,application/pdf",
									onChange: Oe
								}, null, 544),
								(_(), i(e, null, y(de, (t) => a("div", {
									key: t.role,
									class: "mb-4 last:mb-0"
								}, [a("p", Wn, x(t.label), 1), a("div", Gn, [(_(!0), i(e, null, y(t.slots, (t) => (_(), i("div", {
									key: t.key,
									class: "flex flex-col"
								}, [
									a("p", Kn, x(t.label), 1),
									fe(t.key) ? (_(), i("div", qn, [fe(t.key).is_image && pe.value[fe(t.key).id] ? (_(), i("img", {
										key: 0,
										src: pe.value[fe(t.key).id],
										alt: t.label,
										class: "h-full w-full object-cover"
									}, null, 8, Jn)) : fe(t.key).is_image ? (_(), i("div", Xn, [...l[20] ||= [a("svg", {
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
									})], -1)]])) : (_(), i("div", Yn, [...l[19] ||= [a("svg", {
										class: "h-8 w-8",
										fill: "none",
										viewBox: "0 0 24 24",
										stroke: "currentColor"
									}, [a("path", {
										"stroke-linecap": "round",
										"stroke-linejoin": "round",
										"stroke-width": "1.25",
										d: "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
									})], -1), a("span", { class: "text-xs" }, "PDF", -1)]])), a("div", Zn, [c(h, {
										size: "sm",
										variant: "primary",
										onClick: (e) => ge(fe(t.key).id)
									}, {
										default: T(() => [...l[21] ||= [s(" View ", -1)]]),
										_: 1
									}, 8, ["onClick"]), a("button", {
										type: "button",
										class: "flex h-7 w-7 items-center justify-center rounded-full bg-surface text-status-red shadow",
										"aria-label": "Remove",
										onClick: (e) => ke(fe(t.key).id)
									}, [...l[22] ||= [a("svg", {
										class: "h-4 w-4",
										fill: "none",
										viewBox: "0 0 24 24",
										stroke: "currentColor"
									}, [a("path", {
										"stroke-linecap": "round",
										"stroke-linejoin": "round",
										"stroke-width": "2",
										d: "M6 18L18 6M6 6l12 12"
									})], -1)]], 8, Qn)])])) : (_(), i("button", {
										key: 1,
										type: "button",
										class: "flex h-28 flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-line-strong bg-surface-secondary text-subtle transition-colors hover:border-primary-400 hover:text-primary-500",
										disabled: N.value === t.key,
										onClick: (e) => De(t.key)
									}, [N.value === t.key ? (_(), i(e, { key: 0 }, [l[23] ||= a("svg", {
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
									})], -1), l[24] ||= a("span", { class: "text-xs" }, "Uploading…", -1)], 64)) : (_(), i(e, { key: 1 }, [l[25] ||= a("svg", {
										class: "h-6 w-6",
										fill: "none",
										viewBox: "0 0 24 24",
										stroke: "currentColor"
									}, [a("path", {
										"stroke-linecap": "round",
										"stroke-linejoin": "round",
										"stroke-width": "1.5",
										d: "M12 4v16m8-8H4"
									})], -1), l[26] ||= a("span", { class: "text-xs" }, "Upload", -1)], 64))], 8, $n)),
									fe(t.key) ? (_(), i("div", er, [a("span", tr, x(fe(t.key).file_name), 1), a("button", {
										type: "button",
										class: "shrink-0 text-xs text-primary-500 hover:underline",
										onClick: (e) => De(t.key)
									}, " Replace ", 8, nr)])) : r("", !0)
								]))), 128))])])), 64))
							]),
							a("div", rr, [
								l[36] ||= a("p", { class: "mb-3 text-xs font-semibold uppercase tracking-wide text-muted" }, "Trip Financial Summary & Payments", -1),
								a("div", ir, [
									a("div", ar, [
										l[28] ||= a("p", { class: "text-xs font-medium text-muted" }, "Customer Billed (Revenue)", -1),
										a("p", or, x(u.value.money.revenue > 0 ? S(I)(u.value.money.revenue) : "—"), 1),
										a("div", sr, [
											a("span", cr, "Recv: " + x(S(I)(u.value.money.actual_revenue ?? 0)), 1),
											(u.value.money.tds_amount ?? 0) > 0 ? (_(), i("span", lr, "| TDS: " + x(S(I)(u.value.money.tds_amount)), 1)) : r("", !0),
											(u.value.money.customer_due ?? 0) > 0 ? (_(), i("span", ur, "| Due: " + x(S(I)(u.value.money.customer_due)), 1)) : r("", !0)
										])
									]),
									a("div", dr, [
										l[29] ||= a("p", { class: "text-xs font-medium text-muted" }, "Truck Hire & Costs", -1),
										a("p", fr, x(S(I)(u.value.money.cost)), 1),
										a("p", pr, [s(" Hire: " + x(S(I)(u.value.money.cost - u.value.expenses.reduce((e, t) => e + t.amount, 0))) + " ", 1), u.value.expenses.length > 0 ? (_(), i("span", mr, "· Exp: " + x(S(I)(u.value.expenses.reduce((e, t) => e + t.amount, 0))), 1)) : r("", !0)])
									]),
									a("div", hr, [
										l[30] ||= a("p", { class: "text-xs font-medium text-muted" }, "Paid to Truck Owner", -1),
										a("p", gr, x(S(I)(u.value.money.owner_paid)), 1),
										a("p", { class: f(["mt-1 text-[11px] font-semibold", u.value.money.owner_balance > 0 ? "text-amber-600" : "text-status-green"]) }, x(u.value.money.owner_balance > 0 ? `Balance Due: ${S(I)(u.value.money.owner_balance)}` : "✓ All Settled"), 3)
									]),
									a("div", _r, [
										l[31] ||= a("p", { class: "text-xs font-medium text-muted" }, "Net Trip Profit", -1),
										a("p", { class: f(["mt-1 text-lg font-extrabold", u.value.money.profit >= 0 ? "text-status-green" : "text-status-red"]) }, x(S(I)(u.value.money.profit)), 3),
										a("p", vr, x(u.value.money.revenue > 0 ? "Margin: " + (u.value.money.profit / u.value.money.revenue * 100).toFixed(1) + "%" : "Margin: —"), 1)
									])
								]),
								u.value.money.receivable.length > 0 ? (_(), i("div", yr, [l[32] ||= a("p", { class: "mb-2 text-xs font-semibold uppercase tracking-wide text-muted" }, "Customer Collections (Money In)", -1), (_(!0), i(e, null, y(u.value.money.receivable, (t) => (_(), i("div", {
									key: t.invoice_id,
									class: "mb-2 rounded-lg border border-line-light p-3 text-sm"
								}, [a("div", br, [a("div", xr, [a("p", Sr, "🧾 Invoice #" + x(t.invoice_number), 1), a("p", Cr, x(t.customer_name ?? "No customer") + " · Billed " + x(S(I)(t.total)) + " · Due " + x(S(I)(t.due_amount)), 1)]), a("span", { class: f(["shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold", {
									"bg-status-red/10 text-status-red": t.paid_status === "UNPAID",
									"bg-status-yellow/10 text-status-yellow": t.paid_status === "PARTIALLY_PAID",
									"bg-status-green/10 text-status-green": t.paid_status === "PAID"
								}]) }, x(t.paid_status), 3)]), t.payments.length > 0 ? (_(), i("div", wr, [(_(!0), i(e, null, y(t.payments, (e) => (_(), i("div", {
									key: e.id,
									class: "flex items-center justify-between gap-2 rounded-md bg-surface-secondary px-2.5 py-1.5 text-xs"
								}, [a("div", Tr, [a("span", Er, "💰 " + x(e.number ?? "Payment"), 1), a("span", Dr, x(e.date ?? "—") + " · " + x(e.method ?? "Bank"), 1)]), a("div", Or, [a("div", kr, [a("span", Ar, x(S(I)(e.amount + (e.tds_amount ?? 0))), 1), (e.tds_amount ?? 0) > 0 ? (_(), i("span", jr, " Bank: " + x(S(I)(e.amount)) + " | TDS: " + x(S(I)(e.tds_amount)), 1)) : r("", !0)]), e.unique_hash ? (_(), i("button", {
									key: 0,
									type: "button",
									class: "rounded border border-primary-300 bg-primary-50 px-2 py-0.5 text-[11px] font-medium text-primary-700 hover:bg-primary-100",
									onClick: (t) => ue(e.unique_hash)
								}, " 📄 Payment Receipt ", 8, Mr)) : r("", !0)])]))), 128))])) : r("", !0)]))), 128))])) : r("", !0),
								u.value.money.payable.length > 0 ? (_(), i("div", Nr, [l[33] ||= a("p", { class: "mb-2 text-xs font-semibold uppercase tracking-wide text-muted" }, "Truck Owner Hire & Payment Vouchers (Money Out)", -1), (_(!0), i(e, null, y(u.value.money.payable, (t) => (_(), i("div", {
									key: t.invoice_number,
									class: "mb-2 rounded-lg border border-line-light p-3 text-sm"
								}, [
									a("div", Pr, [a("div", Fr, [a("p", Ir, [s("🚛 Lorry Challan #" + x(t.invoice_number) + " ", 1), t.bill_number ? (_(), i("span", Lr, "(Bill #" + x(t.bill_number) + ")", 1)) : r("", !0)]), a("p", Rr, [s(x(t.supplier_name ?? "Truck Owner / Supplier"), 1), t.lorry_no ? (_(), i("span", zr, " · Vehicle: " + x(t.lorry_no), 1)) : r("", !0)])]), t.bill_status ? (_(), i("span", {
										key: 0,
										class: f(["shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold", {
											"bg-status-red/10 text-status-red": t.bill_status === "UNPAID",
											"bg-status-yellow/10 text-status-yellow": t.bill_status === "PARTIAL",
											"bg-status-green/10 text-status-green": t.bill_status === "SETTLED"
										}])
									}, x(t.bill_status), 3)) : r("", !0)]),
									t.bill_total === null ? r("", !0) : (_(), i("p", Br, [s(" Total Hire " + x(S(I)(t.bill_total)) + " · Balance Due ", 1), a("span", Vr, x(S(I)(t.bill_due ?? 0)), 1)])),
									t.payments.length > 0 ? (_(), i("div", Hr, [(_(!0), i(e, null, y(t.payments, (e) => (_(), i("div", {
										key: e.id,
										class: "flex items-center justify-between gap-2 rounded-md bg-surface-secondary px-2.5 py-1.5 text-xs"
									}, [a("div", Ur, [a("span", { class: f(["rounded px-1.5 py-0.5 text-[10px] font-bold uppercase", e.type === "advance" ? "bg-blue-100 text-blue-700" : "bg-emerald-100 text-emerald-700"]) }, x(e.type === "advance" ? "Advance" : "Final Settlement"), 3), a("span", Wr, [s(x(e.date ?? "—"), 1), e.reference ? (_(), i("span", Gr, " · Ref: " + x(e.reference), 1)) : r("", !0)])]), a("div", Kr, [a("span", qr, x(S(I)(e.amount)), 1), a("button", {
										type: "button",
										class: "inline-flex items-center gap-1 rounded border border-indigo-300 bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 shadow-sm hover:bg-indigo-100",
										onClick: (t) => le(e)
									}, " 🎫 View Voucher ", 8, Jr)])]))), 128))])) : r("", !0)
								]))), 128))])) : r("", !0),
								a("div", Yr, [a("span", Xr, [
									l[34] ||= s(" Total Paid to Owner: ", -1),
									a("strong", Zr, x(S(I)(u.value.money.owner_paid)), 1),
									l[35] ||= s(" · Remaining Balance: ", -1),
									a("strong", { class: f(u.value.money.owner_balance > 0 ? "text-amber-600" : "text-status-green") }, x(S(I)(u.value.money.owner_balance)), 3)
								]), u.value.money.bill_status ? (_(), i("span", {
									key: 0,
									class: f(["rounded-full px-2 py-0.5 text-[11px] font-semibold", {
										"bg-status-red/10 text-status-red": u.value.money.bill_status === "UNPAID",
										"bg-status-yellow/10 text-status-yellow": u.value.money.bill_status === "PARTIAL",
										"bg-status-green/10 text-status-green": u.value.money.bill_status === "SETTLED"
									}])
								}, x(u.value.money.bill_status), 3)) : r("", !0)])
							])
						]),
						_: 1
					})])
				], 64)) : r("", !0)]),
				c(Tt, {
					show: O.value,
					client: o.client,
					notify: o.notify,
					type: "lr",
					multiple: "",
					"trip-id": u.value?.id,
					onClose: l[3] ||= (e) => O.value = !1,
					onSelect: Se
				}, null, 8, [
					"show",
					"client",
					"notify",
					"trip-id"
				]),
				c(Tt, {
					show: k.value,
					client: o.client,
					notify: o.notify,
					type: "lorry",
					multiple: "",
					"trip-id": u.value?.id,
					onClose: l[4] ||= (e) => k.value = !1,
					onSelect: Ce
				}, null, 8, [
					"show",
					"client",
					"notify",
					"trip-id"
				]),
				c(en, {
					show: se.value,
					payment: ce.value,
					trip: u.value,
					onClose: l[5] ||= (e) => se.value = !1
				}, null, 8, [
					"show",
					"payment",
					"trip"
				])
			]);
		};
	}
});
//#endregion
//#region ../../node_modules/sortablejs/modular/sortable.esm.js
function $r(e, t) {
	var n = Object.keys(e);
	if (Object.getOwnPropertySymbols) {
		var r = Object.getOwnPropertySymbols(e);
		t && (r = r.filter(function(t) {
			return Object.getOwnPropertyDescriptor(e, t).enumerable;
		})), n.push.apply(n, r);
	}
	return n;
}
function ei(e) {
	for (var t = 1; t < arguments.length; t++) {
		var n = arguments[t] == null ? {} : arguments[t];
		t % 2 ? $r(Object(n), !0).forEach(function(t) {
			ni(e, t, n[t]);
		}) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(n)) : $r(Object(n)).forEach(function(t) {
			Object.defineProperty(e, t, Object.getOwnPropertyDescriptor(n, t));
		});
	}
	return e;
}
function ti(e) {
	"@babel/helpers - typeof";
	return ti = typeof Symbol == "function" && typeof Symbol.iterator == "symbol" ? function(e) {
		return typeof e;
	} : function(e) {
		return e && typeof Symbol == "function" && e.constructor === Symbol && e !== Symbol.prototype ? "symbol" : typeof e;
	}, ti(e);
}
function ni(e, t, n) {
	return t in e ? Object.defineProperty(e, t, {
		value: n,
		enumerable: !0,
		configurable: !0,
		writable: !0
	}) : e[t] = n, e;
}
function ri() {
	return ri = Object.assign || function(e) {
		for (var t = 1; t < arguments.length; t++) {
			var n = arguments[t];
			for (var r in n) Object.prototype.hasOwnProperty.call(n, r) && (e[r] = n[r]);
		}
		return e;
	}, ri.apply(this, arguments);
}
function ii(e, t) {
	if (e == null) return {};
	var n = {}, r = Object.keys(e), i, a;
	for (a = 0; a < r.length; a++) i = r[a], !(t.indexOf(i) >= 0) && (n[i] = e[i]);
	return n;
}
function ai(e, t) {
	if (e == null) return {};
	var n = ii(e, t), r, i;
	if (Object.getOwnPropertySymbols) {
		var a = Object.getOwnPropertySymbols(e);
		for (i = 0; i < a.length; i++) r = a[i], !(t.indexOf(r) >= 0) && Object.prototype.propertyIsEnumerable.call(e, r) && (n[r] = e[r]);
	}
	return n;
}
var oi = "1.14.0";
function si(e) {
	if (typeof window < "u" && window.navigator) return !!/* @__PURE__ */ navigator.userAgent.match(e);
}
var ci = si(/(?:Trident.*rv[ :]?11\.|msie|iemobile|Windows Phone)/i), li = si(/Edge/i), ui = si(/firefox/i), di = si(/safari/i) && !si(/chrome/i) && !si(/android/i), fi = si(/iP(ad|od|hone)/i), pi = si(/chrome/i) && si(/android/i), mi = {
	capture: !1,
	passive: !1
};
function L(e, t, n) {
	e.addEventListener(t, n, !ci && mi);
}
function R(e, t, n) {
	e.removeEventListener(t, n, !ci && mi);
}
function hi(e, t) {
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
function gi(e) {
	return e.host && e !== document && e.host.nodeType ? e.host : e.parentNode;
}
function _i(e, t, n, r) {
	if (e) {
		n ||= document;
		do {
			if (t != null && (t[0] === ">" ? e.parentNode === n && hi(e, t) : hi(e, t)) || r && e === n) return e;
			if (e === n) break;
		} while (e = gi(e));
	}
	return null;
}
var vi = /\s+/g;
function z(e, t, n) {
	e && t && (e.classList ? e.classList[n ? "add" : "remove"](t) : e.className = ((" " + e.className + " ").replace(vi, " ").replace(" " + t + " ", " ") + (n ? " " + t : "")).replace(vi, " "));
}
function B(e, t, n) {
	var r = e && e.style;
	if (r) {
		if (n === void 0) return document.defaultView && document.defaultView.getComputedStyle ? n = document.defaultView.getComputedStyle(e, "") : e.currentStyle && (n = e.currentStyle), t === void 0 ? n : n[t];
		!(t in r) && t.indexOf("webkit") === -1 && (t = "-webkit-" + t), r[t] = n + (typeof n == "string" ? "" : "px");
	}
}
function yi(e, t) {
	var n = "";
	if (typeof e == "string") n = e;
	else do {
		var r = B(e, "transform");
		r && r !== "none" && (n = r + " " + n);
	} while (!t && (e = e.parentNode));
	var i = window.DOMMatrix || window.WebKitCSSMatrix || window.CSSMatrix || window.MSCSSMatrix;
	return i && new i(n);
}
function bi(e, t, n) {
	if (e) {
		var r = e.getElementsByTagName(t), i = 0, a = r.length;
		if (n) for (; i < a; i++) n(r[i], i);
		return r;
	}
	return [];
}
function xi() {
	return document.scrollingElement || document.documentElement;
}
function V(e, t, n, r, i) {
	if (!(!e.getBoundingClientRect && e !== window)) {
		var a, o, s, c, l, u, d;
		if (e !== window && e.parentNode && e !== xi() ? (a = e.getBoundingClientRect(), o = a.top, s = a.left, c = a.bottom, l = a.right, u = a.height, d = a.width) : (o = 0, s = 0, c = window.innerHeight, l = window.innerWidth, u = window.innerHeight, d = window.innerWidth), (t || n) && e !== window && (i ||= e.parentNode, !ci)) do
			if (i && i.getBoundingClientRect && (B(i, "transform") !== "none" || n && B(i, "position") !== "static")) {
				var f = i.getBoundingClientRect();
				o -= f.top + parseInt(B(i, "border-top-width")), s -= f.left + parseInt(B(i, "border-left-width")), c = o + a.height, l = s + a.width;
				break;
			}
		while (i = i.parentNode);
		if (r && e !== window) {
			var p = yi(i || e), m = p && p.a, h = p && p.d;
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
function Si(e, t, n) {
	for (var r = Oi(e, !0), i = V(e)[t]; r;) {
		var a = V(r)[n], o = void 0;
		if (o = n === "top" || n === "left" ? i >= a : i <= a, !o) return r;
		if (r === xi()) break;
		r = Oi(r, !1);
	}
	return !1;
}
function Ci(e, t, n, r) {
	for (var i = 0, a = 0, o = e.children; a < o.length;) {
		if (o[a].style.display !== "none" && o[a] !== Q.ghost && (r || o[a] !== Q.dragged) && _i(o[a], n.draggable, e, !1)) {
			if (i === t) return o[a];
			i++;
		}
		a++;
	}
	return null;
}
function wi(e, t) {
	for (var n = e.lastElementChild; n && (n === Q.ghost || B(n, "display") === "none" || t && !hi(n, t));) n = n.previousElementSibling;
	return n || null;
}
function Ti(e, t) {
	var n = 0;
	if (!e || !e.parentNode) return -1;
	for (; e = e.previousElementSibling;) e.nodeName.toUpperCase() !== "TEMPLATE" && e !== Q.clone && (!t || hi(e, t)) && n++;
	return n;
}
function Ei(e) {
	var t = 0, n = 0, r = xi();
	if (e) do {
		var i = yi(e), a = i.a, o = i.d;
		t += e.scrollLeft * a, n += e.scrollTop * o;
	} while (e !== r && (e = e.parentNode));
	return [t, n];
}
function Di(e, t) {
	for (var n in e) if (e.hasOwnProperty(n)) {
		for (var r in t) if (t.hasOwnProperty(r) && t[r] === e[n][r]) return Number(n);
	}
	return -1;
}
function Oi(e, t) {
	if (!e || !e.getBoundingClientRect) return xi();
	var n = e, r = !1;
	do
		if (n.clientWidth < n.scrollWidth || n.clientHeight < n.scrollHeight) {
			var i = B(n);
			if (n.clientWidth < n.scrollWidth && (i.overflowX == "auto" || i.overflowX == "scroll") || n.clientHeight < n.scrollHeight && (i.overflowY == "auto" || i.overflowY == "scroll")) {
				if (!n.getBoundingClientRect || n === document.body) return xi();
				if (r || t) return n;
				r = !0;
			}
		}
	while (n = n.parentNode);
	return xi();
}
function ki(e, t) {
	if (e && t) for (var n in t) t.hasOwnProperty(n) && (e[n] = t[n]);
	return e;
}
function Ai(e, t) {
	return Math.round(e.top) === Math.round(t.top) && Math.round(e.left) === Math.round(t.left) && Math.round(e.height) === Math.round(t.height) && Math.round(e.width) === Math.round(t.width);
}
var ji;
function Mi(e, t) {
	return function() {
		if (!ji) {
			var n = arguments, r = this;
			n.length === 1 ? e.call(r, n[0]) : e.apply(r, n), ji = setTimeout(function() {
				ji = void 0;
			}, t);
		}
	};
}
function Ni() {
	clearTimeout(ji), ji = void 0;
}
function Pi(e, t, n) {
	e.scrollLeft += t, e.scrollTop += n;
}
function Fi(e) {
	var t = window.Polymer, n = window.jQuery || window.Zepto;
	return t && t.dom ? t.dom(e).cloneNode(!0) : n ? n(e).clone(!0)[0] : e.cloneNode(!0);
}
var H = "Sortable" + (/* @__PURE__ */ new Date()).getTime();
function Ii() {
	var e = [], t;
	return {
		captureAnimationState: function() {
			e = [], this.options.animation && [].slice.call(this.el.children).forEach(function(t) {
				if (!(B(t, "display") === "none" || t === Q.ghost)) {
					e.push({
						target: t,
						rect: V(t)
					});
					var n = ei({}, e[e.length - 1].rect);
					if (t.thisAnimationDuration) {
						var r = yi(t, !0);
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
			e.splice(Di(e, { target: t }), 1);
		},
		animateAll: function(n) {
			var r = this;
			if (!this.options.animation) {
				clearTimeout(t), typeof n == "function" && n();
				return;
			}
			var i = !1, a = 0;
			e.forEach(function(e) {
				var t = 0, n = e.target, o = n.fromRect, s = V(n), c = n.prevFromRect, l = n.prevToRect, u = e.rect, d = yi(n, !0);
				d && (s.top -= d.f, s.left -= d.e), n.toRect = s, n.thisAnimationDuration && Ai(c, s) && !Ai(o, s) && (u.top - s.top) / (u.left - s.left) === (o.top - s.top) / (o.left - s.left) && (t = Ri(u, c, l, r.options)), Ai(s, o) || (n.prevFromRect = o, n.prevToRect = s, t ||= r.options.animation, r.animate(n, u, s, t)), t && (i = !0, a = Math.max(a, t), clearTimeout(n.animationResetTimer), n.animationResetTimer = setTimeout(function() {
					n.animationTime = 0, n.prevFromRect = null, n.fromRect = null, n.prevToRect = null, n.thisAnimationDuration = null;
				}, t), n.thisAnimationDuration = t);
			}), clearTimeout(t), i ? t = setTimeout(function() {
				typeof n == "function" && n();
			}, a) : typeof n == "function" && n(), e = [];
		},
		animate: function(e, t, n, r) {
			if (r) {
				B(e, "transition", ""), B(e, "transform", "");
				var i = yi(this.el), a = i && i.a, o = i && i.d, s = (t.left - n.left) / (a || 1), c = (t.top - n.top) / (o || 1);
				e.animatingX = !!s, e.animatingY = !!c, B(e, "transform", "translate3d(" + s + "px," + c + "px,0)"), this.forRepaintDummy = Li(e), B(e, "transition", "transform " + r + "ms" + (this.options.easing ? " " + this.options.easing : "")), B(e, "transform", "translate3d(0,0,0)"), typeof e.animated == "number" && clearTimeout(e.animated), e.animated = setTimeout(function() {
					B(e, "transition", ""), B(e, "transform", ""), e.animated = !1, e.animatingX = !1, e.animatingY = !1;
				}, r);
			}
		}
	};
}
function Li(e) {
	return e.offsetWidth;
}
function Ri(e, t, n, r) {
	return Math.sqrt((t.top - e.top) ** 2 + (t.left - e.left) ** 2) / Math.sqrt((t.top - n.top) ** 2 + (t.left - n.left) ** 2) * r.animation;
}
var zi = [], Bi = { initializeByDefault: !0 }, Vi = {
	mount: function(e) {
		for (var t in Bi) Bi.hasOwnProperty(t) && !(t in e) && (e[t] = Bi[t]);
		zi.forEach(function(t) {
			if (t.pluginName === e.pluginName) throw `Sortable: Cannot mount plugin ${e.pluginName} more than once`;
		}), zi.push(e);
	},
	pluginEvent: function(e, t, n) {
		var r = this;
		this.eventCanceled = !1, n.cancel = function() {
			r.eventCanceled = !0;
		};
		var i = e + "Global";
		zi.forEach(function(r) {
			t[r.pluginName] && (t[r.pluginName][i] && t[r.pluginName][i](ei({ sortable: t }, n)), t.options[r.pluginName] && t[r.pluginName][e] && t[r.pluginName][e](ei({ sortable: t }, n)));
		});
	},
	initializePlugins: function(e, t, n, r) {
		for (var i in zi.forEach(function(r) {
			var i = r.pluginName;
			if (!(!e.options[i] && !r.initializeByDefault)) {
				var a = new r(e, t, e.options);
				a.sortable = e, a.options = e.options, e[i] = a, ri(n, a.defaults);
			}
		}), e.options) if (e.options.hasOwnProperty(i)) {
			var a = this.modifyOption(e, i, e.options[i]);
			a !== void 0 && (e.options[i] = a);
		}
	},
	getEventProperties: function(e, t) {
		var n = {};
		return zi.forEach(function(r) {
			typeof r.eventProperties == "function" && ri(n, r.eventProperties.call(t[r.pluginName], e));
		}), n;
	},
	modifyOption: function(e, t, n) {
		var r;
		return zi.forEach(function(i) {
			e[i.pluginName] && i.optionListeners && typeof i.optionListeners[t] == "function" && (r = i.optionListeners[t].call(e[i.pluginName], n));
		}), r;
	}
};
function Hi(e) {
	var t = e.sortable, n = e.rootEl, r = e.name, i = e.targetEl, a = e.cloneEl, o = e.toEl, s = e.fromEl, c = e.oldIndex, l = e.newIndex, u = e.oldDraggableIndex, d = e.newDraggableIndex, f = e.originalEvent, p = e.putSortable, m = e.extraEventProperties;
	if (t ||= n && n[H], t) {
		var h, g = t.options, _ = "on" + r.charAt(0).toUpperCase() + r.substr(1);
		window.CustomEvent && !ci && !li ? h = new CustomEvent(r, {
			bubbles: !0,
			cancelable: !0
		}) : (h = document.createEvent("Event"), h.initEvent(r, !0, !0)), h.to = o || n, h.from = s || n, h.item = i || n, h.clone = a, h.oldIndex = c, h.newIndex = l, h.oldDraggableIndex = u, h.newDraggableIndex = d, h.originalEvent = f, h.pullMode = p ? p.lastPutMode : void 0;
		var v = ei(ei({}, m), Vi.getEventProperties(r, t));
		for (var y in v) h[y] = v[y];
		n && n.dispatchEvent(h), g[_] && g[_].call(t, h);
	}
}
var Ui = ["evt"], U = function(e, t) {
	var n = arguments.length > 2 && arguments[2] !== void 0 ? arguments[2] : {}, r = n.evt, i = ai(n, Ui);
	Vi.pluginEvent.bind(Q)(e, t, ei({
		dragEl: G,
		parentEl: K,
		ghostEl: q,
		rootEl: J,
		nextEl: Wi,
		lastDownEl: Gi,
		cloneEl: Y,
		cloneHidden: Ki,
		dragStarted: sa,
		putSortable: X,
		activeSortable: Q.active,
		originalEvent: r,
		oldIndex: qi,
		oldDraggableIndex: Yi,
		newIndex: Ji,
		newDraggableIndex: Xi,
		hideGhostForTarget: Ta,
		unhideGhostForTarget: Ea,
		cloneNowHidden: function() {
			Ki = !0;
		},
		cloneNowShown: function() {
			Ki = !1;
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
	Hi(ei({
		putSortable: X,
		cloneEl: Y,
		targetEl: G,
		rootEl: J,
		oldIndex: qi,
		oldDraggableIndex: Yi,
		newIndex: Ji,
		newDraggableIndex: Xi
	}, e));
}
var G, K, q, J, Wi, Gi, Y, Ki, qi, Ji, Yi, Xi, Zi, X, Qi = !1, $i = !1, ea = [], ta, na, ra, ia, aa, oa, sa, ca, la, ua = !1, da = !1, fa, Z, pa = [], ma = !1, ha = [], ga = typeof document < "u", _a = fi, va = li || ci ? "cssFloat" : "float", ya = ga && !pi && !fi && "draggable" in document.createElement("div"), ba = function() {
	if (ga) {
		if (ci) return !1;
		var e = document.createElement("x");
		return e.style.cssText = "pointer-events:auto", e.style.pointerEvents === "auto";
	}
}(), xa = function(e, t) {
	var n = B(e), r = parseInt(n.width) - parseInt(n.paddingLeft) - parseInt(n.paddingRight) - parseInt(n.borderLeftWidth) - parseInt(n.borderRightWidth), i = Ci(e, 0, t), a = Ci(e, 1, t), o = i && B(i), s = a && B(a), c = o && parseInt(o.marginLeft) + parseInt(o.marginRight) + V(i).width, l = s && parseInt(s.marginLeft) + parseInt(s.marginRight) + V(a).width;
	if (n.display === "flex") return n.flexDirection === "column" || n.flexDirection === "column-reverse" ? "vertical" : "horizontal";
	if (n.display === "grid") return n.gridTemplateColumns.split(" ").length <= 1 ? "vertical" : "horizontal";
	if (i && o.float && o.float !== "none") {
		var u = o.float === "left" ? "left" : "right";
		return a && (s.clear === "both" || s.clear === u) ? "vertical" : "horizontal";
	}
	return i && (o.display === "block" || o.display === "flex" || o.display === "table" || o.display === "grid" || c >= r && n[va] === "none" || a && n[va] === "none" && c + l > r) ? "vertical" : "horizontal";
}, Sa = function(e, t, n) {
	var r = n ? e.left : e.top, i = n ? e.right : e.bottom, a = n ? e.width : e.height, o = n ? t.left : t.top, s = n ? t.right : t.bottom, c = n ? t.width : t.height;
	return r === o || i === s || r + a / 2 === o + c / 2;
}, Ca = function(e, t) {
	var n;
	return ea.some(function(r) {
		var i = r[H].options.emptyInsertThreshold;
		if (!(!i || wi(r))) {
			var a = V(r), o = e >= a.left - i && e <= a.right + i, s = t >= a.top - i && t <= a.bottom + i;
			if (o && s) return n = r;
		}
	}), n;
}, wa = function(e) {
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
	(!r || ti(r) != "object") && (r = { name: r }), n.name = r.name, n.checkPull = t(r.pull, !0), n.checkPut = t(r.put), n.revertClone = r.revertClone, e.group = n;
}, Ta = function() {
	!ba && q && B(q, "display", "none");
}, Ea = function() {
	!ba && q && B(q, "display", "");
};
ga && document.addEventListener("click", function(e) {
	if ($i) return e.preventDefault(), e.stopPropagation && e.stopPropagation(), e.stopImmediatePropagation && e.stopImmediatePropagation(), $i = !1, !1;
}, !0);
var Da = function(e) {
	if (G) {
		e = e.touches ? e.touches[0] : e;
		var t = Ca(e.clientX, e.clientY);
		if (t) {
			var n = {};
			for (var r in e) e.hasOwnProperty(r) && (n[r] = e[r]);
			n.target = n.rootEl = t, n.preventDefault = void 0, n.stopPropagation = void 0, t[H]._onDragOver(n);
		}
	}
}, Oa = function(e) {
	G && G.parentNode[H]._isOutsideThisEl(e.target);
};
function Q(e, t) {
	if (!(e && e.nodeType && e.nodeType === 1)) throw `Sortable: \`el\` must be an HTMLElement, not ${{}.toString.call(e)}`;
	this.el = e, this.options = t = ri({}, t), e[H] = this;
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
			return xa(e, this.options);
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
		supportPointer: Q.supportPointer !== !1 && "PointerEvent" in window && !di,
		emptyInsertThreshold: 5
	};
	for (var r in Vi.initializePlugins(this, e, n), n) !(r in t) && (t[r] = n[r]);
	for (var i in wa(t), this) i.charAt(0) === "_" && typeof this[i] == "function" && (this[i] = this[i].bind(this));
	this.nativeDraggable = t.forceFallback ? !1 : ya, this.nativeDraggable && (this.options.touchStartThreshold = 1), t.supportPointer ? L(e, "pointerdown", this._onTapStart) : (L(e, "mousedown", this._onTapStart), L(e, "touchstart", this._onTapStart)), this.nativeDraggable && (L(e, "dragover", this), L(e, "dragenter", this)), ea.push(this.el), t.store && t.store.get && this.sort(t.store.get(this) || []), ri(this, Ii());
}
Q.prototype = {
	constructor: Q,
	_isOutsideThisEl: function(e) {
		!this.el.contains(e) && e !== this.el && (ca = null);
	},
	_getDirection: function(e, t) {
		return typeof this.options.direction == "function" ? this.options.direction.call(this, e, t, G) : this.options.direction;
	},
	_onTapStart: function(e) {
		if (e.cancelable) {
			var t = this, n = this.el, r = this.options, i = r.preventOnFilter, a = e.type, o = e.touches && e.touches[0] || e.pointerType && e.pointerType === "touch" && e, s = (o || e).target, c = e.target.shadowRoot && (e.path && e.path[0] || e.composedPath && e.composedPath()[0]) || s, l = r.filter;
			if (Ra(n), !G && !(/mousedown|pointerdown/.test(a) && e.button !== 0 || r.disabled) && !c.isContentEditable && !(!this.nativeDraggable && di && s && s.tagName.toUpperCase() === "SELECT") && (s = _i(s, r.draggable, n, !1), !(s && s.animated) && Gi !== s)) {
				if (qi = Ti(s), Yi = Ti(s, r.draggable), typeof l == "function") {
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
					if (r = _i(c, r.trim(), n, !1), r) return W({
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
				r.handle && !_i(c, r.handle, n, !1) || this._prepareDragStart(e, o, s);
			}
		}
	},
	_prepareDragStart: function(e, t, n) {
		var r = this, i = r.el, a = r.options, o = i.ownerDocument, s;
		if (n && !G && n.parentNode === i) {
			var c = V(n);
			if (J = i, G = n, K = G.parentNode, Wi = G.nextSibling, Gi = n, Zi = a.group, Q.dragged = G, ta = {
				target: G,
				clientX: (t || e).clientX,
				clientY: (t || e).clientY
			}, aa = ta.clientX - c.left, oa = ta.clientY - c.top, this._lastX = (t || e).clientX, this._lastY = (t || e).clientY, G.style["will-change"] = "all", s = function() {
				if (U("delayEnded", r, { evt: e }), Q.eventCanceled) {
					r._onDrop();
					return;
				}
				r._disableDelayedDragEvents(), !ui && r.nativeDraggable && (G.draggable = !0), r._triggerDragStart(e, t), W({
					sortable: r,
					name: "choose",
					originalEvent: e
				}), z(G, a.chosenClass, !0);
			}, a.ignore.split(",").forEach(function(e) {
				bi(G, e.trim(), ja);
			}), L(o, "dragover", Da), L(o, "mousemove", Da), L(o, "touchmove", Da), L(o, "mouseup", r._onDrop), L(o, "touchend", r._onDrop), L(o, "touchcancel", r._onDrop), ui && this.nativeDraggable && (this.options.touchStartThreshold = 4, G.draggable = !0), U("delayStart", this, { evt: e }), a.delay && (!a.delayOnTouchOnly || t) && (!this.nativeDraggable || !(li || ci))) {
				if (Q.eventCanceled) {
					this._onDrop();
					return;
				}
				L(o, "mouseup", r._disableDelayedDrag), L(o, "touchend", r._disableDelayedDrag), L(o, "touchcancel", r._disableDelayedDrag), L(o, "mousemove", r._delayedDragTouchMoveHandler), L(o, "touchmove", r._delayedDragTouchMoveHandler), a.supportPointer && L(o, "pointermove", r._delayedDragTouchMoveHandler), r._dragStartTimer = setTimeout(s, a.delay);
			} else s();
		}
	},
	_delayedDragTouchMoveHandler: function(e) {
		var t = e.touches ? e.touches[0] : e;
		Math.max(Math.abs(t.clientX - this._lastX), Math.abs(t.clientY - this._lastY)) >= Math.floor(this.options.touchStartThreshold / (this.nativeDraggable && window.devicePixelRatio || 1)) && this._disableDelayedDrag();
	},
	_disableDelayedDrag: function() {
		G && ja(G), clearTimeout(this._dragStartTimer), this._disableDelayedDragEvents();
	},
	_disableDelayedDragEvents: function() {
		var e = this.el.ownerDocument;
		R(e, "mouseup", this._disableDelayedDrag), R(e, "touchend", this._disableDelayedDrag), R(e, "touchcancel", this._disableDelayedDrag), R(e, "mousemove", this._delayedDragTouchMoveHandler), R(e, "touchmove", this._delayedDragTouchMoveHandler), R(e, "pointermove", this._delayedDragTouchMoveHandler);
	},
	_triggerDragStart: function(e, t) {
		t ||= e.pointerType == "touch" && e, !this.nativeDraggable || t ? this.options.supportPointer ? L(document, "pointermove", this._onTouchMove) : t ? L(document, "touchmove", this._onTouchMove) : L(document, "mousemove", this._onTouchMove) : (L(G, "dragend", this), L(J, "dragstart", this._onDragStart));
		try {
			document.selection ? za(function() {
				document.selection.empty();
			}) : window.getSelection().removeAllRanges();
		} catch {}
	},
	_dragStarted: function(e, t) {
		if (Qi = !1, J && G) {
			U("dragStarted", this, { evt: t }), this.nativeDraggable && L(document, "dragover", Oa);
			var n = this.options;
			!e && z(G, n.dragClass, !1), z(G, n.ghostClass, !0), Q.active = this, e && this._appendGhost(), W({
				sortable: this,
				name: "start",
				originalEvent: t
			});
		} else this._nulling();
	},
	_emulateDragOver: function() {
		if (na) {
			this._lastX = na.clientX, this._lastY = na.clientY, Ta();
			for (var e = document.elementFromPoint(na.clientX, na.clientY), t = e; e && e.shadowRoot && (e = e.shadowRoot.elementFromPoint(na.clientX, na.clientY), e !== t);) t = e;
			if (G.parentNode[H]._isOutsideThisEl(e), t) do {
				if (t[H]) {
					var n = void 0;
					if (n = t[H]._onDragOver({
						clientX: na.clientX,
						clientY: na.clientY,
						target: e,
						rootEl: t
					}), n && !this.options.dragoverBubble) break;
				}
				e = t;
			} while (t = t.parentNode);
			Ea();
		}
	},
	_onTouchMove: function(e) {
		if (ta) {
			var t = this.options, n = t.fallbackTolerance, r = t.fallbackOffset, i = e.touches ? e.touches[0] : e, a = q && yi(q, !0), o = q && a && a.a, s = q && a && a.d, c = _a && Z && Ei(Z), l = (i.clientX - ta.clientX + r.x) / (o || 1) + (c ? c[0] - pa[0] : 0) / (o || 1), u = (i.clientY - ta.clientY + r.y) / (s || 1) + (c ? c[1] - pa[1] : 0) / (s || 1);
			if (!Q.active && !Qi) {
				if (n && Math.max(Math.abs(i.clientX - this._lastX), Math.abs(i.clientY - this._lastY)) < n) return;
				this._onDragStart(e, !0);
			}
			if (q) {
				a ? (a.e += l - (ra || 0), a.f += u - (ia || 0)) : a = {
					a: 1,
					b: 0,
					c: 0,
					d: 1,
					e: l,
					f: u
				};
				var d = `matrix(${a.a},${a.b},${a.c},${a.d},${a.e},${a.f})`;
				B(q, "webkitTransform", d), B(q, "mozTransform", d), B(q, "msTransform", d), B(q, "transform", d), ra = l, ia = u, na = i;
			}
			e.cancelable && e.preventDefault();
		}
	},
	_appendGhost: function() {
		if (!q) {
			var e = this.options.fallbackOnBody ? document.body : J, t = V(G, !0, _a, !0, e), n = this.options;
			if (_a) {
				for (Z = e; B(Z, "position") === "static" && B(Z, "transform") === "none" && Z !== document;) Z = Z.parentNode;
				Z !== document.body && Z !== document.documentElement ? (Z === document && (Z = xi()), t.top += Z.scrollTop, t.left += Z.scrollLeft) : Z = xi(), pa = Ei(Z);
			}
			q = G.cloneNode(!0), z(q, n.ghostClass, !1), z(q, n.fallbackClass, !0), z(q, n.dragClass, !0), B(q, "transition", ""), B(q, "transform", ""), B(q, "box-sizing", "border-box"), B(q, "margin", 0), B(q, "top", t.top), B(q, "left", t.left), B(q, "width", t.width), B(q, "height", t.height), B(q, "opacity", "0.8"), B(q, "position", _a ? "absolute" : "fixed"), B(q, "zIndex", "100000"), B(q, "pointerEvents", "none"), Q.ghost = q, e.appendChild(q), B(q, "transform-origin", aa / parseInt(q.style.width) * 100 + "% " + oa / parseInt(q.style.height) * 100 + "%");
		}
	},
	_onDragStart: function(e, t) {
		var n = this, r = e.dataTransfer, i = n.options;
		if (U("dragStart", this, { evt: e }), Q.eventCanceled) {
			this._onDrop();
			return;
		}
		U("setupClone", this), Q.eventCanceled || (Y = Fi(G), Y.draggable = !1, Y.style["will-change"] = "", this._hideClone(), z(Y, this.options.chosenClass, !1), Q.clone = Y), n.cloneId = za(function() {
			U("clone", n), !Q.eventCanceled && (n.options.removeCloneOnHide || J.insertBefore(Y, G), n._hideClone(), W({
				sortable: n,
				name: "clone"
			}));
		}), !t && z(G, i.dragClass, !0), t ? ($i = !0, n._loopId = setInterval(n._emulateDragOver, 50)) : (R(document, "mouseup", n._onDrop), R(document, "touchend", n._onDrop), R(document, "touchcancel", n._onDrop), r && (r.effectAllowed = "move", i.setData && i.setData.call(n, r, G)), L(document, "drop", n), B(G, "transform", "translateZ(0)")), Qi = !0, n._dragStartId = za(n._dragStarted.bind(n, t, e)), L(document, "selectstart", n), sa = !0, di && B(document.body, "user-select", "none");
	},
	_onDragOver: function(e) {
		var t = this.el, n = e.target, r, i, a, o = this.options, s = o.group, c = Q.active, l = Zi === s, u = o.sort, d = X || c, f, p = this, m = !1;
		if (ma) return;
		function h(o, s) {
			U(o, p, ei({
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
					return Aa(J, t, G, r, n, V(n), e, i);
				},
				changed: v
			}, s));
		}
		function g() {
			h("dragOverAnimationCapture"), p.captureAnimationState(), p !== d && d.captureAnimationState();
		}
		function _(r) {
			return h("dragOverCompleted", { insertion: r }), r && (l ? c._hideClone() : c._showClone(p), p !== d && (z(G, X ? X.options.ghostClass : c.options.ghostClass, !1), z(G, o.ghostClass, !0)), X !== p && p !== Q.active ? X = p : p === Q.active && X && (X = null), d === p && (p._ignoreWhileAnimating = n), p.animateAll(function() {
				h("dragOverAnimationComplete"), p._ignoreWhileAnimating = null;
			}), p !== d && (d.animateAll(), d._ignoreWhileAnimating = null)), (n === G && !G.animated || n === t && !n.animated) && (ca = null), !o.dragoverBubble && !e.rootEl && n !== document && (G.parentNode[H]._isOutsideThisEl(e.target), !r && Da(e)), !o.dragoverBubble && e.stopPropagation && e.stopPropagation(), m = !0;
		}
		function v() {
			Ji = Ti(G), Xi = Ti(G, o.draggable), W({
				sortable: p,
				name: "change",
				toEl: t,
				newIndex: Ji,
				newDraggableIndex: Xi,
				originalEvent: e
			});
		}
		if (e.preventDefault !== void 0 && e.cancelable && e.preventDefault(), n = _i(n, o.draggable, t, !0), h("dragOver"), Q.eventCanceled) return m;
		if (G.contains(e.target) || n.animated && n.animatingX && n.animatingY || p._ignoreWhileAnimating === n) return _(!1);
		if ($i = !1, c && !o.disabled && (l ? u || (a = K !== J) : X === this || (this.lastPutMode = Zi.checkPull(this, c, G, e)) && s.checkPut(this, c, G, e))) {
			if (f = this._getDirection(e, n) === "vertical", r = V(G), h("dragOverValid"), Q.eventCanceled) return m;
			if (a) return K = J, g(), this._hideClone(), h("revert"), Q.eventCanceled || (Wi ? J.insertBefore(G, Wi) : J.appendChild(G)), _(!0);
			var y = wi(t, o.draggable);
			if (!y || Pa(e, f, this) && !y.animated) {
				if (y === G) return _(!1);
				if (y && t === e.target && (n = y), n && (i = V(n)), Aa(J, t, G, r, n, i, e, !!n) !== !1) return g(), t.appendChild(G), K = t, v(), _(!0);
			} else if (y && Na(e, f, this)) {
				var b = Ci(t, 0, o, !0);
				if (b === G) return _(!1);
				if (n = b, i = V(n), Aa(J, t, G, r, n, i, e, !1) !== !1) return g(), t.insertBefore(G, b), K = t, v(), _(!0);
			} else if (n.parentNode === t) {
				i = V(n);
				var x = 0, S, C = G.parentNode !== t, w = !Sa(G.animated && G.toRect || r, n.animated && n.toRect || i, f), T = f ? "top" : "left", E = Si(n, "top", "top") || Si(G, "top", "top"), D = E ? E.scrollTop : void 0;
				ca !== n && (S = i[T], ua = !1, da = !w && o.invertSwap || C), x = Fa(e, n, i, f, w ? 1 : o.swapThreshold, o.invertedSwapThreshold == null ? o.swapThreshold : o.invertedSwapThreshold, da, ca === n);
				var O;
				if (x !== 0) {
					var k = Ti(G);
					do
						k -= x, O = K.children[k];
					while (O && (B(O, "display") === "none" || O === q));
				}
				if (x === 0 || O === n) return _(!1);
				ca = n, la = x;
				var A = n.nextElementSibling, j = !1;
				j = x === 1;
				var M = Aa(J, t, G, r, n, i, e, j);
				if (M !== !1) return (M === 1 || M === -1) && (j = M === 1), ma = !0, setTimeout(Ma, 30), g(), j && !A ? t.appendChild(G) : n.parentNode.insertBefore(G, j ? A : n), E && Pi(E, 0, D - E.scrollTop), K = G.parentNode, S !== void 0 && !da && (fa = Math.abs(S - V(n)[T])), v(), _(!0);
			}
			if (t.contains(G)) return _(!1);
		}
		return !1;
	},
	_ignoreWhileAnimating: null,
	_offMoveEvents: function() {
		R(document, "mousemove", this._onTouchMove), R(document, "touchmove", this._onTouchMove), R(document, "pointermove", this._onTouchMove), R(document, "dragover", Da), R(document, "mousemove", Da), R(document, "touchmove", Da);
	},
	_offUpEvents: function() {
		var e = this.el.ownerDocument;
		R(e, "mouseup", this._onDrop), R(e, "touchend", this._onDrop), R(e, "pointerup", this._onDrop), R(e, "touchcancel", this._onDrop), R(document, "selectstart", this);
	},
	_onDrop: function(e) {
		var t = this.el, n = this.options;
		if (Ji = Ti(G), Xi = Ti(G, n.draggable), U("drop", this, { evt: e }), K = G && G.parentNode, Ji = Ti(G), Xi = Ti(G, n.draggable), Q.eventCanceled) {
			this._nulling();
			return;
		}
		Qi = !1, da = !1, ua = !1, clearInterval(this._loopId), clearTimeout(this._dragStartTimer), Ba(this.cloneId), Ba(this._dragStartId), this.nativeDraggable && (R(document, "drop", this), R(t, "dragstart", this._onDragStart)), this._offMoveEvents(), this._offUpEvents(), di && B(document.body, "user-select", ""), B(G, "transform", ""), e && (sa && (e.cancelable && e.preventDefault(), !n.dropBubble && e.stopPropagation()), q && q.parentNode && q.parentNode.removeChild(q), (J === K || X && X.lastPutMode !== "clone") && Y && Y.parentNode && Y.parentNode.removeChild(Y), G && (this.nativeDraggable && R(G, "dragend", this), ja(G), G.style["will-change"] = "", sa && !Qi && z(G, X ? X.options.ghostClass : this.options.ghostClass, !1), z(G, this.options.chosenClass, !1), W({
			sortable: this,
			name: "unchoose",
			toEl: K,
			newIndex: null,
			newDraggableIndex: null,
			originalEvent: e
		}), J === K ? Ji !== qi && Ji >= 0 && (W({
			sortable: this,
			name: "update",
			toEl: K,
			originalEvent: e
		}), W({
			sortable: this,
			name: "sort",
			toEl: K,
			originalEvent: e
		})) : (Ji >= 0 && (W({
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
		})), X && X.save()), Q.active && ((Ji == null || Ji === -1) && (Ji = qi, Xi = Yi), W({
			sortable: this,
			name: "end",
			toEl: K,
			originalEvent: e
		}), this.save()))), this._nulling();
	},
	_nulling: function() {
		U("nulling", this), J = G = K = q = Wi = Y = Gi = Ki = ta = na = sa = Ji = Xi = qi = Yi = ca = la = X = Zi = Q.dragged = Q.ghost = Q.clone = Q.active = null, ha.forEach(function(e) {
			e.checked = !0;
		}), ha.length = ra = ia = 0;
	},
	handleEvent: function(e) {
		switch (e.type) {
			case "drop":
			case "dragend":
				this._onDrop(e);
				break;
			case "dragenter":
			case "dragover":
				G && (this._onDragOver(e), ka(e));
				break;
			case "selectstart":
				e.preventDefault();
				break;
		}
	},
	toArray: function() {
		for (var e = [], t, n = this.el.children, r = 0, i = n.length, a = this.options; r < i; r++) t = n[r], _i(t, a.draggable, this.el, !1) && e.push(t.getAttribute(a.dataIdAttr) || La(t));
		return e;
	},
	sort: function(e, t) {
		var n = {}, r = this.el;
		this.toArray().forEach(function(e, t) {
			var i = r.children[t];
			_i(i, this.options.draggable, r, !1) && (n[e] = i);
		}, this), t && this.captureAnimationState(), e.forEach(function(e) {
			n[e] && (r.removeChild(n[e]), r.appendChild(n[e]));
		}), t && this.animateAll();
	},
	save: function() {
		var e = this.options.store;
		e && e.set && e.set(this);
	},
	closest: function(e, t) {
		return _i(e, t || this.options.draggable, this.el, !1);
	},
	option: function(e, t) {
		var n = this.options;
		if (t === void 0) return n[e];
		var r = Vi.modifyOption(this, e, t);
		r === void 0 ? n[e] = t : n[e] = r, e === "group" && wa(n);
	},
	destroy: function() {
		U("destroy", this);
		var e = this.el;
		e[H] = null, R(e, "mousedown", this._onTapStart), R(e, "touchstart", this._onTapStart), R(e, "pointerdown", this._onTapStart), this.nativeDraggable && (R(e, "dragover", this), R(e, "dragenter", this)), Array.prototype.forEach.call(e.querySelectorAll("[draggable]"), function(e) {
			e.removeAttribute("draggable");
		}), this._onDrop(), this._disableDelayedDragEvents(), ea.splice(ea.indexOf(this.el), 1), this.el = e = null;
	},
	_hideClone: function() {
		if (!Ki) {
			if (U("hideClone", this), Q.eventCanceled) return;
			B(Y, "display", "none"), this.options.removeCloneOnHide && Y.parentNode && Y.parentNode.removeChild(Y), Ki = !0;
		}
	},
	_showClone: function(e) {
		if (e.lastPutMode !== "clone") {
			this._hideClone();
			return;
		}
		if (Ki) {
			if (U("showClone", this), Q.eventCanceled) return;
			G.parentNode == J && !this.options.group.revertClone ? J.insertBefore(Y, G) : Wi ? J.insertBefore(Y, Wi) : J.appendChild(Y), this.options.group.revertClone && this.animate(G, Y), B(Y, "display", ""), Ki = !1;
		}
	}
};
function ka(e) {
	e.dataTransfer && (e.dataTransfer.dropEffect = "move"), e.cancelable && e.preventDefault();
}
function Aa(e, t, n, r, i, a, o, s) {
	var c, l = e[H], u = l.options.onMove, d;
	return window.CustomEvent && !ci && !li ? c = new CustomEvent("move", {
		bubbles: !0,
		cancelable: !0
	}) : (c = document.createEvent("Event"), c.initEvent("move", !0, !0)), c.to = t, c.from = e, c.dragged = n, c.draggedRect = r, c.related = i || t, c.relatedRect = a || V(t), c.willInsertAfter = s, c.originalEvent = o, e.dispatchEvent(c), u && (d = u.call(l, c, o)), d;
}
function ja(e) {
	e.draggable = !1;
}
function Ma() {
	ma = !1;
}
function Na(e, t, n) {
	var r = V(Ci(n.el, 0, n.options, !0)), i = 10;
	return t ? e.clientX < r.left - i || e.clientY < r.top && e.clientX < r.right : e.clientY < r.top - i || e.clientY < r.bottom && e.clientX < r.left;
}
function Pa(e, t, n) {
	var r = V(wi(n.el, n.options.draggable)), i = 10;
	return t ? e.clientX > r.right + i || e.clientX <= r.right && e.clientY > r.bottom && e.clientX >= r.left : e.clientX > r.right && e.clientY > r.top || e.clientX <= r.right && e.clientY > r.bottom + i;
}
function Fa(e, t, n, r, i, a, o, s) {
	var c = r ? e.clientY : e.clientX, l = r ? n.height : n.width, u = r ? n.top : n.left, d = r ? n.bottom : n.right, f = !1;
	if (!o) {
		if (s && fa < l * i) {
			if (!ua && (la === 1 ? c > u + l * a / 2 : c < d - l * a / 2) && (ua = !0), ua) f = !0;
			else if (la === 1 ? c < u + fa : c > d - fa) return -la;
		} else if (c > u + l * (1 - i) / 2 && c < d - l * (1 - i) / 2) return Ia(t);
	}
	return f ||= o, f && (c < u + l * a / 2 || c > d - l * a / 2) ? c > u + l / 2 ? 1 : -1 : 0;
}
function Ia(e) {
	return Ti(G) < Ti(e) ? 1 : -1;
}
function La(e) {
	for (var t = e.tagName + e.className + e.src + e.href + e.textContent, n = t.length, r = 0; n--;) r += t.charCodeAt(n);
	return r.toString(36);
}
function Ra(e) {
	ha.length = 0;
	for (var t = e.getElementsByTagName("input"), n = t.length; n--;) {
		var r = t[n];
		r.checked && ha.push(r);
	}
}
function za(e) {
	return setTimeout(e, 0);
}
function Ba(e) {
	return clearTimeout(e);
}
ga && L(document, "touchmove", function(e) {
	(Q.active || Qi) && e.cancelable && e.preventDefault();
}), Q.utils = {
	on: L,
	off: R,
	css: B,
	find: bi,
	is: function(e, t) {
		return !!_i(e, t, e, !1);
	},
	extend: ki,
	throttle: Mi,
	closest: _i,
	toggleClass: z,
	clone: Fi,
	index: Ti,
	nextTick: za,
	cancelNextTick: Ba,
	detectDirection: xa,
	getChild: Ci
}, Q.get = function(e) {
	return e[H];
}, Q.mount = function() {
	var e = [...arguments];
	e[0].constructor === Array && (e = e[0]), e.forEach(function(e) {
		if (!e.prototype || !e.prototype.constructor) throw `Sortable: Mounted plugin must be a constructor function, not ${{}.toString.call(e)}`;
		e.utils && (Q.utils = ei(ei({}, Q.utils), e.utils)), Vi.mount(e);
	});
}, Q.create = function(e, t) {
	return new Q(e, t);
}, Q.version = oi;
var $ = [], Va, Ha, Ua = !1, Wa, Ga, Ka, qa;
function Ja() {
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
			this.sortable.nativeDraggable ? L(document, "dragover", this._handleAutoScroll) : this.options.supportPointer ? L(document, "pointermove", this._handleFallbackAutoScroll) : t.touches ? L(document, "touchmove", this._handleFallbackAutoScroll) : L(document, "mousemove", this._handleFallbackAutoScroll);
		},
		dragOverCompleted: function(e) {
			var t = e.originalEvent;
			!this.options.dragOverBubble && !t.rootEl && this._handleAutoScroll(t);
		},
		drop: function() {
			this.sortable.nativeDraggable ? R(document, "dragover", this._handleAutoScroll) : (R(document, "pointermove", this._handleFallbackAutoScroll), R(document, "touchmove", this._handleFallbackAutoScroll), R(document, "mousemove", this._handleFallbackAutoScroll)), Xa(), Ya(), Ni();
		},
		nulling: function() {
			Ka = Ha = Va = Ua = qa = Wa = Ga = null, $.length = 0;
		},
		_handleFallbackAutoScroll: function(e) {
			this._handleAutoScroll(e, !0);
		},
		_handleAutoScroll: function(e, t) {
			var n = this, r = (e.touches ? e.touches[0] : e).clientX, i = (e.touches ? e.touches[0] : e).clientY, a = document.elementFromPoint(r, i);
			if (Ka = e, t || this.options.forceAutoScrollFallback || li || ci || di) {
				Za(e, this.options, a, t);
				var o = Oi(a, !0);
				Ua && (!qa || r !== Wa || i !== Ga) && (qa && Xa(), qa = setInterval(function() {
					var a = Oi(document.elementFromPoint(r, i), !0);
					a !== o && (o = a, Ya()), Za(e, n.options, a, t);
				}, 10), Wa = r, Ga = i);
			} else {
				if (!this.options.bubbleScroll || Oi(a, !0) === xi()) {
					Ya();
					return;
				}
				Za(e, this.options, Oi(a, !1), !1);
			}
		}
	}, ri(e, {
		pluginName: "scroll",
		initializeByDefault: !0
	});
}
function Ya() {
	$.forEach(function(e) {
		clearInterval(e.pid);
	}), $ = [];
}
function Xa() {
	clearInterval(qa);
}
var Za = Mi(function(e, t, n, r) {
	if (t.scroll) {
		var i = (e.touches ? e.touches[0] : e).clientX, a = (e.touches ? e.touches[0] : e).clientY, o = t.scrollSensitivity, s = t.scrollSpeed, c = xi(), l = !1, u;
		Ha !== n && (Ha = n, Ya(), Va = t.scroll, u = t.scrollFn, Va === !0 && (Va = Oi(n, !0)));
		var d = 0, f = Va;
		do {
			var p = f, m = V(p), h = m.top, g = m.bottom, _ = m.left, v = m.right, y = m.width, b = m.height, x = void 0, S = void 0, C = p.scrollWidth, w = p.scrollHeight, T = B(p), E = p.scrollLeft, D = p.scrollTop;
			p === c ? (x = y < C && (T.overflowX === "auto" || T.overflowX === "scroll" || T.overflowX === "visible"), S = b < w && (T.overflowY === "auto" || T.overflowY === "scroll" || T.overflowY === "visible")) : (x = y < C && (T.overflowX === "auto" || T.overflowX === "scroll"), S = b < w && (T.overflowY === "auto" || T.overflowY === "scroll"));
			var O = x && (Math.abs(v - i) <= o && E + y < C) - (Math.abs(_ - i) <= o && !!E), k = S && (Math.abs(g - a) <= o && D + b < w) - (Math.abs(h - a) <= o && !!D);
			if (!$[d]) for (var A = 0; A <= d; A++) $[A] || ($[A] = {});
			($[d].vx != O || $[d].vy != k || $[d].el !== p) && ($[d].el = p, $[d].vx = O, $[d].vy = k, clearInterval($[d].pid), (O != 0 || k != 0) && (l = !0, $[d].pid = setInterval(function() {
				r && this.layer === 0 && Q.active._onTouchMove(Ka);
				var t = $[this.layer].vy ? $[this.layer].vy * s : 0, n = $[this.layer].vx ? $[this.layer].vx * s : 0;
				typeof u == "function" && u.call(Q.dragged.parentNode[H], n, t, e, Ka, $[this.layer].el) !== "continue" || Pi($[this.layer].el, n, t);
			}.bind({ layer: d }), 24))), d++;
		} while (t.bubbleScroll && f !== c && (f = Oi(f, !1)));
		Ua = l;
	}
}, 30), Qa = function(e) {
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
function $a() {}
$a.prototype = {
	startIndex: null,
	dragStart: function(e) {
		this.startIndex = e.oldDraggableIndex;
	},
	onSpill: function(e) {
		var t = e.dragEl, n = e.putSortable;
		this.sortable.captureAnimationState(), n && n.captureAnimationState();
		var r = Ci(this.sortable.el, this.startIndex, this.options);
		r ? this.sortable.el.insertBefore(t, r) : this.sortable.el.appendChild(t), this.sortable.animateAll(), n && n.animateAll();
	},
	drop: Qa
}, ri($a, { pluginName: "revertOnSpill" });
function eo() {}
eo.prototype = {
	onSpill: function(e) {
		var t = e.dragEl, n = e.putSortable || this.sortable;
		n.captureAnimationState(), t.parentNode && t.parentNode.removeChild(t), n.animateAll();
	},
	drop: Qa
}, ri(eo, { pluginName: "removeOnSpill" }), Q.mount(new Ja()), Q.mount(eo, $a);
//#endregion
//#region resources/js/support/filters.ts
var to = {
	customer: "",
	status: "",
	search: "",
	unbilled: ""
};
function no(e) {
	return {
		customer: uo(e.customer),
		status: uo(e.status),
		search: lo(e.search).slice(0, 200),
		unbilled: e.unbilled === "1" ? "1" : ""
	};
}
function ro(e) {
	let t = {};
	for (let n of [
		"customer",
		"status",
		"search",
		"unbilled"
	]) e[n] !== "" && (t[n] = e[n]);
	return t;
}
function io(e) {
	return e.customer !== "" || e.status !== "" || e.search !== "" || e.unbilled !== "";
}
function ao(e) {
	return [
		e.customer,
		e.status,
		e.search,
		e.unbilled
	].join("|");
}
function oo(e, t) {
	return e.customer === t.customer && e.status === t.status && e.search === t.search && e.unbilled === t.unbilled;
}
function so(e) {
	let t = Number(e);
	return e !== "" && Number.isInteger(t) && t > 0 ? t : null;
}
function co(e) {
	let t = {}, n = so(e.customer);
	n !== null && (t.customer_id = n);
	let r = so(e.status);
	return r !== null && (t.status_id = r), e.search !== "" && (t.search = e.search), e.unbilled === "1" && (t.unbilled_only = !0), t;
}
function lo(e) {
	let t = Array.isArray(e) ? e[0] : e;
	return typeof t == "string" ? t.trim() : "";
}
function uo(e) {
	let t = lo(e);
	return so(t) === null ? "" : t;
}
//#endregion
//#region resources/js/components/TripCard.vue?vue&type=script&setup=true&lang.ts
var fo = { class: "flex items-start justify-between gap-2" }, po = { class: "min-w-0" }, mo = { class: "text-sm font-medium text-heading" }, ho = {
	key: 0,
	class: "inline-flex items-center rounded bg-primary-50 px-1.5 py-0.5 text-[11px] font-semibold text-primary-700 ring-1 ring-inset ring-primary-700/20 ml-1"
}, go = {
	key: 1,
	class: "text-xs font-normal text-muted"
}, _o = { class: "relative" }, vo = {
	key: 0,
	class: "absolute end-0 z-20 mt-1 w-44 rounded-lg border border-line-default bg-surface p-1 shadow-lg"
}, yo = ["onClick"], bo = { class: "mt-1 text-sm text-muted" }, xo = {
	key: 0,
	class: "mt-1 text-xs text-muted"
}, So = { class: "mt-2 flex items-center justify-between gap-2" }, Co = { class: "text-xs text-muted" }, wo = { class: "mt-2.5 flex items-center justify-between border-t border-line-light pt-2 text-xs" }, To = { class: "flex items-center gap-1.5" }, Eo = {
	key: 0,
	class: "inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20"
}, Do = {
	key: 1,
	class: "inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20"
}, Oo = {
	key: 2,
	class: "inline-flex items-center rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-medium text-slate-500 ring-1 ring-inset ring-slate-500/20"
}, ko = {
	key: 0,
	class: "text-[11px] text-muted"
}, Ao = { class: "font-medium text-heading" }, jo = /* @__PURE__ */ l({
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
		let c = n, l = o, u = v(!1), d = t(() => `${c.trip.from_city ?? "—"} → ${c.trip.to_city ?? "—"}`), p = t(() => {
			let e = c.trip.customers.map((e) => e.name).filter(Boolean);
			return e.length === 0 ? "No customer" : e.length === 1 ? e[0] : `${e[0]} +${e.length - 1} more`;
		}), m = t(() => c.trip.profit >= 0 ? "text-status-green" : "text-status-red");
		function h() {
			u.value = !u.value;
		}
		function g(e) {
			u.value = !1, l("move", c.trip, e);
		}
		function b() {
			u.value = !1, l("cancel", c.trip);
		}
		return (t, o) => (_(), i("article", {
			class: f(["cursor-pointer rounded-lg border border-line-default bg-surface p-3 shadow-sm hover:bg-hover", { "opacity-50": n.trip.cancelled_at }]),
			onClick: o[1] ||= (e) => l("open", n.trip)
		}, [
			a("div", fo, [a("div", po, [a("p", mo, [
				s(" #" + x(n.trip.trip_no) + " ", 1),
				n.trip.lr_numbers && n.trip.lr_numbers.length > 1 ? (_(), i("span", ho, " LR: " + x(n.trip.lr_numbers.join(", ")), 1)) : n.trip.lr_numbers && n.trip.lr_numbers.length === 1 && String(n.trip.trip_no) !== String(n.trip.lr_numbers[0]) ? (_(), i("span", go, " [LR: " + x(n.trip.lr_numbers[0]) + "] ", 1)) : r("", !0),
				s(" · " + x(d.value), 1)
			])]), a("div", _o, [a("button", {
				type: "button",
				class: "rounded px-1 text-muted hover:text-heading",
				"aria-label": "Trip menu",
				onClick: D(h, ["stop"])
			}, " ⋯ "), u.value ? (_(), i("div", vo, [
				a("button", {
					type: "button",
					class: "block w-full rounded px-2 py-1.5 text-start text-sm text-body hover:bg-hover",
					onClick: o[0] ||= (e) => {
						u.value = !1, l("open", n.trip);
					}
				}, " Open trip "),
				o[2] ||= a("p", { class: "px-2 pt-1.5 pb-0.5 text-xs font-medium text-muted" }, "Move to", -1),
				(_(!0), i(e, null, y(n.statuses.filter((e) => e.id !== n.trip.status_id), (e) => (_(), i("button", {
					key: e.id,
					type: "button",
					class: "block w-full rounded px-2 py-1.5 text-start text-sm text-body hover:bg-hover",
					onClick: (t) => g(e.id)
				}, x(e.name), 9, yo))), 128)),
				n.trip.cancelled_at ? r("", !0) : (_(), i("button", {
					key: 0,
					type: "button",
					class: "mt-1 block w-full rounded px-2 py-1.5 text-start text-sm text-status-red hover:bg-hover",
					onClick: b
				}, " Cancel trip "))
			])) : r("", !0)])]),
			a("p", bo, x(p.value), 1),
			n.trip.lorry_no ? (_(), i("p", xo, "🚛 " + x(n.trip.lorry_no), 1)) : r("", !0),
			a("div", So, [a("span", Co, x(n.trip.lr_count) + " LR" + x(n.trip.lr_count === 1 ? "" : "s") + " · " + x(S(I)(n.trip.revenue)), 1), a("span", { class: f(["text-sm font-semibold", m.value]) }, x(S(I)(n.trip.profit)), 3)]),
			a("div", wo, [a("div", To, [n.trip.payment_status === "PAID" ? (_(), i("span", Eo, " ● Payment Received ")) : n.trip.payment_status === "PARTIALLY_PAID" ? (_(), i("span", Do, " ◐ Partially Paid ")) : (_(), i("span", Oo, " ○ Payment Pending "))]), n.trip.customer_paid > 0 ? (_(), i("span", ko, [o[3] ||= s(" Recvd: ", -1), a("span", Ao, x(S(I)(n.trip.customer_paid)), 1)])) : r("", !0)])
		], 2));
	}
}), Mo = {
	key: 0,
	class: "py-16 text-center text-sm text-muted"
}, No = {
	key: 1,
	class: "rounded-2xl border border-line-default bg-surface p-12 text-center"
}, Po = {
	key: 2,
	class: "flex items-stretch gap-4 overflow-x-auto pb-4"
}, Fo = { class: "flex shrink-0 items-center gap-2 border-b border-line-default bg-surface-secondary/60 px-3 py-2.5" }, Io = { class: "truncate text-sm font-semibold text-heading" }, Lo = { class: "ml-auto shrink-0 rounded-full bg-surface-tertiary px-2 py-0.5 text-xs font-medium text-muted" }, Ro = ["data-status-id"], zo = ["data-trip-id"], Bo = {
	key: 0,
	class: "rounded-lg border border-dashed border-line-default py-10 text-center text-xs text-muted"
}, Vo = /* @__PURE__ */ l({
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
		let o = n, s = v([]), l = v(!0), u = /* @__PURE__ */ new Map(), d = /* @__PURE__ */ new Map(), f = t(() => o.statuses.map((e) => ({
			status: e,
			cards: s.value.filter((t) => t.status_id === e.id).sort((e, t) => e.board_position - t.board_position)
		}))), g = t(() => !l.value && s.value.length === 0);
		async function b() {
			l.value = !0;
			try {
				s.value = await Me(o.client, co(o.filters));
			} catch (e) {
				o.notify("error", F(e, "Unable to load the board."));
			} finally {
				l.value = !1;
			}
		}
		h(() => {
			b();
		}), w(() => ao(o.filters), () => void b()), m(() => {
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
		function C(e) {
			let t = e.item, n = e.oldIndex ?? 0;
			t.parentNode?.removeChild(t), e.from.insertBefore(t, e.from.children[n] ?? null);
		}
		async function T(e) {
			let t = Number(e.item.dataset.tripId), n = Number(e.to.dataset.statusId);
			if (C(e), !t || Number.isNaN(n)) return;
			let r = s.value.filter((e) => e.status_id === n && e.id !== t).sort((e, t) => e.board_position - t.board_position), i = r[e.newIndex - 1], a = r[e.newIndex], c = i && a ? (i.board_position + a.board_position) / 2 : a ? a.board_position - .5 : i ? i.board_position + .5 : 1;
			try {
				let e = await Ie(o.client, t, n, c);
				s.value = s.value.map((n) => n.id === t ? e : n);
			} catch (e) {
				o.notify("error", F(e, "Unable to move the trip.")), await b();
			}
		}
		function E(e) {
			o.router?.push(j.trip(e.id));
		}
		async function D(e, t) {
			try {
				let n = await Ie(o.client, e.id, t, null);
				s.value = s.value.map((t) => t.id === e.id ? n : t);
			} catch (e) {
				o.notify("error", F(e, "Unable to move the trip."));
			}
		}
		async function O(e) {
			if (window.confirm("Cancel this trip? It stays on the board, greyed out, and can never be billed.")) try {
				await Le(o.client, e.id), o.notify("success", "Trip cancelled."), await b();
			} catch (e) {
				o.notify("error", F(e, "Unable to cancel the trip."));
			}
		}
		return (t, o) => (_(), i("div", null, [l.value ? (_(), i("div", Mo, "Loading the board…")) : g.value ? (_(), i("div", No, [...o[0] ||= [
			a("p", { class: "text-4xl" }, "🚛", -1),
			a("h2", { class: "mt-3 text-lg font-semibold text-heading" }, "No trips yet", -1),
			a("p", { class: "mt-1 text-sm text-muted" }, " Your board is empty. Create your first trip by mapping an existing LR Receipt, or by filling a new one. ", -1)
		]])) : (_(), i("div", Po, [(_(!0), i(e, null, y(f.value, (t) => (_(), i("section", {
			key: t.status.id,
			class: "flex w-72 shrink-0 flex-col overflow-hidden rounded-xl border border-line-default bg-surface shadow-sm"
		}, [
			a("div", {
				class: "h-1 w-full shrink-0",
				style: p({ backgroundColor: t.status.colour ?? "#94a3b8" })
			}, null, 4),
			a("header", Fo, [
				a("span", {
					class: "inline-block h-2.5 w-2.5 shrink-0 rounded-full",
					style: p({ backgroundColor: t.status.colour ?? "#94a3b8" })
				}, null, 4),
				a("span", Io, x(t.status.name), 1),
				a("span", Lo, x(t.cards.length), 1)
			]),
			a("div", {
				ref_for: !0,
				ref: S(t.status.id),
				"data-status-id": t.status.id,
				class: "min-h-44 flex-1 space-y-2 bg-surface-tertiary/40 p-2"
			}, [(_(!0), i(e, null, y(t.cards, (e) => (_(), i("div", {
				key: e.id,
				"data-trip-id": e.id
			}, [c(jo, {
				trip: e,
				statuses: n.statuses,
				onOpen: E,
				onMove: D,
				onCancel: O
			}, null, 8, ["trip", "statuses"])], 8, zo))), 128)), t.cards.length === 0 ? (_(), i("p", Bo, " Drop trips here ")) : r("", !0)], 8, Ro)
		]))), 128))]))]));
	}
}), Ho = {
	key: 0,
	class: "flex items-center justify-between gap-3 px-4 py-3 border-t md:px-6 border-line-light"
}, Uo = { class: "text-sm text-muted tabular" }, Wo = { class: "md:hidden" }, Go = { class: "hidden md:inline" }, Ko = { class: "font-medium text-body" }, qo = { class: "font-medium text-body" }, Jo = { class: "font-medium text-body" }, Yo = ["aria-label"], Xo = ["disabled", "aria-label"], Zo = { class: "items-center hidden gap-1 md:flex" }, Qo = ["aria-current"], $o = {
	key: 1,
	class: "px-1 text-sm text-subtle",
	"aria-hidden": "true"
}, es = [
	"aria-current",
	"aria-label",
	"onClick"
], ts = {
	key: 2,
	class: "px-1 text-sm text-subtle",
	"aria-hidden": "true"
}, ns = ["aria-current", "aria-label"], rs = ["disabled", "aria-label"], is = "flex items-center justify-center w-10 h-10 md:w-8 md:h-8 rounded-lg border border-line-default text-body hover:bg-hover disabled:opacity-40 disabled:pointer-events-none", as = /* @__PURE__ */ l({
	__name: "TripTablePagination",
	props: { pagination: {} },
	emits: ["pageChange"],
	setup(n, { emit: o }) {
		let l = n, u = o, d = ee();
		function p(e, t) {
			let n = d(e);
			return n && n !== e ? n : t;
		}
		let m = t(() => l.pagination.totalPages === void 0 ? [] : k()), h = t(() => l.pagination.currentPage >= 4 || l.pagination.totalPages < 10), g = t(() => l.pagination.totalPages > 1 && (l.pagination.currentPage <= l.pagination.totalPages - 3 || l.pagination.totalPages < 10)), v = t(() => l.pagination.currentPage >= 4 && l.pagination.totalPages >= 10), S = t(() => l.pagination.currentPage <= l.pagination.totalPages - 3 && l.pagination.totalPages >= 10), C = t(() => l.pagination.totalPages === void 0 || l.pagination.count === 0 ? !1 : l.pagination.totalPages > 1);
		function w(e) {
			return ["min-w-8 h-8 px-2 rounded-lg text-sm font-medium tabular transition-colors", D(e) ? "bg-primary-50 text-primary-700 font-semibold" : "text-body hover:bg-hover"].join(" ");
		}
		let T = t(() => l.pagination.currentPage * l.pagination.limit - (l.pagination.limit - 1)), E = t(() => Math.min(l.pagination.currentPage * l.pagination.limit, l.pagination.totalCount));
		function D(e) {
			return (l.pagination.currentPage || 1) === e;
		}
		function O(e) {
			e === l.pagination.currentPage || e > l.pagination.totalPages || e < 1 || u("pageChange", e);
		}
		function k() {
			let e = [], t = 2, n = l.pagination.totalPages - 1;
			l.pagination.totalPages >= 10 && (t = Math.max(1, l.pagination.currentPage - 2), n = Math.min(l.pagination.currentPage + 2, l.pagination.totalPages));
			for (let r = t; r <= n; r++) e.push(r);
			return e;
		}
		return (t, o) => {
			let l = b("BaseIcon");
			return C.value ? (_(), i("div", Ho, [a("p", Uo, [n.pagination.limit && n.pagination.currentPage ? (_(), i(e, { key: 0 }, [a("span", Wo, x(n.pagination.currentPage) + " / " + x(n.pagination.totalPages), 1), a("span", Go, [
				s(x(p("general.pagination.showing", "Showing")) + " ", 1),
				a("span", Ko, x(T.value), 1),
				s(" " + x(p("general.pagination.to", "to")) + " ", 1),
				a("span", qo, x(E.value), 1),
				s(" " + x(p("general.pagination.of", "of")) + " ", 1),
				a("span", Jo, x(n.pagination.totalCount), 1),
				s(" " + x(p("general.pagination.results", "results")), 1)
			])], 64)) : r("", !0)]), a("nav", {
				class: "flex items-center gap-1",
				"aria-label": p("general.pagination.label", "Pagination")
			}, [
				a("button", {
					type: "button",
					class: f(is),
					disabled: n.pagination.currentPage === 1,
					"aria-label": p("general.pagination.previous", "Previous"),
					onClick: o[0] ||= (e) => O(n.pagination.currentPage - 1)
				}, [c(l, {
					name: "ChevronLeftIcon",
					class: "w-4 h-4"
				})], 8, Xo),
				a("div", Zo, [
					h.value ? (_(), i("button", {
						key: 0,
						type: "button",
						class: f(w(1)),
						"aria-current": D(1) ? "page" : void 0,
						"aria-label": "Page 1",
						onClick: o[1] ||= (e) => O(1)
					}, " 1 ", 10, Qo)) : r("", !0),
					v.value ? (_(), i("span", $o, "…")) : r("", !0),
					(_(!0), i(e, null, y(m.value, (e) => (_(), i("button", {
						key: e,
						type: "button",
						class: f(w(e)),
						"aria-current": D(e) ? "page" : void 0,
						"aria-label": `Page ${e}`,
						onClick: (t) => O(e)
					}, x(e), 11, es))), 128)),
					S.value ? (_(), i("span", ts, "…")) : r("", !0),
					g.value ? (_(), i("button", {
						key: 3,
						type: "button",
						class: f(w(n.pagination.totalPages)),
						"aria-current": D(n.pagination.totalPages) ? "page" : void 0,
						"aria-label": `Page ${n.pagination.totalPages}`,
						onClick: o[2] ||= (e) => O(n.pagination.totalPages)
					}, x(n.pagination.totalPages), 11, ns)) : r("", !0)
				]),
				a("button", {
					type: "button",
					class: f(is),
					disabled: n.pagination.currentPage === n.pagination.totalPages,
					"aria-label": p("general.pagination.next", "Next"),
					onClick: o[3] ||= (e) => O(n.pagination.currentPage + 1)
				}, [c(l, {
					name: "ChevronRightIcon",
					class: "w-4 h-4"
				})], 8, rs)
			], 8, Yo)])) : r("", !0);
		};
	}
}), os = {
	key: 0,
	class: "py-16 text-center text-sm text-muted"
}, ss = {
	key: 1,
	class: "rounded-2xl border border-line-default bg-surface p-12 text-center"
}, cs = {
	key: 2,
	class: "rounded-2xl border border-line-default bg-surface overflow-hidden"
}, ls = { class: "overflow-x-auto" }, us = { class: "w-full text-sm" }, ds = { class: "border-b border-line-default text-start text-xs uppercase text-muted" }, fs = ["onClick"], ps = { class: "p-3 font-medium text-heading" }, ms = { class: "flex items-center gap-1.5 flex-wrap" }, hs = {
	key: 0,
	class: "inline-flex items-center rounded-md bg-primary-50 px-1.5 py-0.5 text-xs font-semibold text-primary-700 ring-1 ring-inset ring-primary-700/20",
	title: "Consolidated Multi-LR Load"
}, gs = {
	key: 1,
	class: "text-xs font-normal text-muted"
}, _s = { class: "p-3 text-body" }, vs = { class: "p-3 text-body" }, ys = { class: "p-3 text-body" }, bs = { class: "p-3 text-body" }, xs = { class: "p-3 text-end text-body font-medium" }, Ss = { class: "p-3 text-end font-medium text-emerald-600" }, Cs = { class: "p-3 text-end font-medium text-indigo-600" }, ws = { class: "p-3 text-end text-body" }, Ts = /* @__PURE__ */ l({
	__name: "TripsListView",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {},
		filters: {},
		statuses: {},
		loading: { type: Boolean }
	},
	setup(o) {
		let s = o, l = v([]), u = v(!0), d = v(1), m = v(10), g = v("trip_no"), C = v("desc");
		function T(e) {
			g.value === e ? C.value = C.value === "asc" ? "desc" : "asc" : (g.value = e, C.value = [
				"revenue",
				"actual_revenue",
				"tds_amount",
				"cost",
				"profit",
				"trip_no"
			].includes(e) ? "desc" : "asc"), d.value = 1;
		}
		let E = t(() => !u.value && l.value.length === 0), D = t(() => {
			let e = [...l.value], t = g.value, n = C.value === "asc" ? 1 : -1;
			return e.sort((e, r) => {
				let i = 0;
				switch (t) {
					case "trip_no":
						i = e.trip_no - r.trip_no;
						break;
					case "route": {
						let t = `${e.from_city ?? ""} ${e.to_city ?? ""}`.trim().toLowerCase(), n = `${r.from_city ?? ""} ${r.to_city ?? ""}`.trim().toLowerCase();
						i = t.localeCompare(n);
						break;
					}
					case "customer": {
						let t = (e.customers[0]?.name ?? "").toLowerCase(), n = (r.customers[0]?.name ?? "").toLowerCase();
						i = t.localeCompare(n);
						break;
					}
					case "lorry": {
						let t = (e.lorry_no ?? "").toLowerCase(), n = (r.lorry_no ?? "").toLowerCase();
						i = t.localeCompare(n);
						break;
					}
					case "status":
						i = e.status_id - r.status_id;
						break;
					case "revenue":
						i = e.revenue - r.revenue;
						break;
					case "actual_revenue":
						i = e.actual_revenue - r.actual_revenue;
						break;
					case "tds_amount":
						i = e.tds_amount - r.tds_amount;
						break;
					case "cost":
						i = e.cost - r.cost;
						break;
					case "profit":
						i = e.profit - r.profit;
						break;
				}
				return i === 0 ? r.trip_no - e.trip_no : i * n;
			});
		}), O = t(() => D.value.length), k = t(() => Math.max(1, Math.ceil(O.value / m.value))), A = t(() => {
			let e = (d.value - 1) * m.value;
			return D.value.slice(e, e + m.value);
		}), M = t(() => ({
			currentPage: d.value,
			totalPages: k.value,
			totalCount: O.value,
			count: A.value.length,
			limit: m.value
		}));
		function N(e) {
			d.value = e, typeof window < "u" && window.scrollTo({
				top: 0,
				behavior: "smooth"
			});
		}
		let ee = (e) => s.statuses.find((t) => t.id === e)?.name ?? "—", te = (e) => s.statuses.find((t) => t.id === e)?.colour ?? "#6366f1";
		async function ne() {
			u.value = !0;
			try {
				l.value = await Me(s.client, co(s.filters));
			} catch (e) {
				s.notify("error", F(e, "Unable to load trips."));
			} finally {
				u.value = !1;
			}
		}
		h(() => {
			ne();
		}), w(() => ao(s.filters), () => {
			d.value = 1, ne();
		}), w(k, (e) => {
			d.value > e && (d.value = e);
		});
		function re(e) {
			s.router?.push(j.trip(e.id));
		}
		return (t, o) => {
			let s = b("BaseIcon");
			return _(), i("div", null, [u.value ? (_(), i("div", os, "Loading trips…")) : E.value ? (_(), i("div", ss, [...o[10] ||= [
				a("p", { class: "text-4xl" }, "🚛", -1),
				a("h2", { class: "mt-3 text-lg font-semibold text-heading" }, "No trips found", -1),
				a("p", { class: "mt-1 text-sm text-muted" }, "Try adjusting your filters, or create a new trip.", -1)
			]])) : (_(), i("div", cs, [a("div", ls, [a("table", us, [a("thead", null, [a("tr", ds, [
				a("th", { class: f(["p-3 text-start font-medium", { "text-heading font-semibold": g.value === "trip_no" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none",
					onClick: o[0] ||= (e) => T("trip_no")
				}, [o[11] ||= a("span", null, "#", -1), g.value === "trip_no" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2),
				a("th", { class: f(["p-3 text-start font-medium", { "text-heading font-semibold": g.value === "route" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none",
					onClick: o[1] ||= (e) => T("route")
				}, [o[12] ||= a("span", null, "Route", -1), g.value === "route" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2),
				a("th", { class: f(["p-3 text-start font-medium", { "text-heading font-semibold": g.value === "customer" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none",
					onClick: o[2] ||= (e) => T("customer")
				}, [o[13] ||= a("span", null, "Customer", -1), g.value === "customer" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2),
				a("th", { class: f(["p-3 text-start font-medium", { "text-heading font-semibold": g.value === "lorry" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none",
					onClick: o[3] ||= (e) => T("lorry")
				}, [o[14] ||= a("span", null, "Lorry", -1), g.value === "lorry" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2),
				a("th", { class: f(["p-3 text-start font-medium", { "text-heading font-semibold": g.value === "status" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none",
					onClick: o[4] ||= (e) => T("status")
				}, [o[15] ||= a("span", null, "Status", -1), g.value === "status" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2),
				a("th", { class: f(["p-3 text-end font-medium", { "text-heading font-semibold": g.value === "revenue" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center justify-end gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none ms-auto",
					onClick: o[5] ||= (e) => T("revenue")
				}, [o[16] ||= a("span", null, "Billed Rev", -1), g.value === "revenue" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2),
				a("th", { class: f(["p-3 text-end font-medium", { "text-heading font-semibold": g.value === "actual_revenue" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center justify-end gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none ms-auto",
					onClick: o[6] ||= (e) => T("actual_revenue")
				}, [o[17] ||= a("span", null, "Actual Rev (Bank)", -1), g.value === "actual_revenue" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2),
				a("th", { class: f(["p-3 text-end font-medium", { "text-heading font-semibold": g.value === "tds_amount" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center justify-end gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none ms-auto",
					onClick: o[7] ||= (e) => T("tds_amount")
				}, [o[18] ||= a("span", null, "TDS", -1), g.value === "tds_amount" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2),
				a("th", { class: f(["p-3 text-end font-medium", { "text-heading font-semibold": g.value === "cost" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center justify-end gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none ms-auto",
					onClick: o[8] ||= (e) => T("cost")
				}, [o[19] ||= a("span", null, "Cost", -1), g.value === "cost" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2),
				a("th", { class: f(["p-3 text-end font-medium", { "text-heading font-semibold": g.value === "profit" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center justify-end gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none ms-auto",
					onClick: o[9] ||= (e) => T("profit")
				}, [o[20] ||= a("span", null, "Profit", -1), g.value === "profit" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2)
			])]), a("tbody", null, [(_(!0), i(e, null, y(A.value, (e) => (_(), i("tr", {
				key: e.id,
				class: f(["cursor-pointer border-b border-line-light hover:bg-hover", { "opacity-50": e.cancelled_at }]),
				onClick: (t) => re(e)
			}, [
				a("td", ps, [a("div", ms, [a("span", null, "#" + x(e.trip_no), 1), e.lr_numbers && e.lr_numbers.length > 1 ? (_(), i("span", hs, " LR: " + x(e.lr_numbers.join(", ")), 1)) : e.lr_numbers && e.lr_numbers.length === 1 && String(e.trip_no) !== String(e.lr_numbers[0]) ? (_(), i("span", gs, " [LR: " + x(e.lr_numbers[0]) + "] ", 1)) : r("", !0)])]),
				a("td", _s, x(e.from_city ?? "—") + " → " + x(e.to_city ?? "—"), 1),
				a("td", vs, x(e.customers.map((e) => e.name).filter(Boolean).join(", ") || "—"), 1),
				a("td", ys, x(e.lorry_no ?? "—"), 1),
				a("td", bs, [a("span", {
					class: "inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold",
					style: p({
						backgroundColor: te(e.status_id) + "20",
						color: te(e.status_id)
					})
				}, x(ee(e.status_id)), 5)]),
				a("td", xs, x(e.revenue > 0 ? S(I)(e.revenue) : "—"), 1),
				a("td", Ss, x(e.actual_revenue > 0 ? S(I)(e.actual_revenue) : e.revenue > 0 ? S(I)(0) : "—"), 1),
				a("td", Cs, x(e.tds_amount > 0 ? S(I)(e.tds_amount) : e.revenue > 0 || e.actual_revenue > 0 ? S(I)(0) : "—"), 1),
				a("td", ws, x(S(I)(e.cost)), 1),
				a("td", { class: f(["p-3 text-end font-semibold", e.profit >= 0 ? "text-status-green" : "text-status-red"]) }, x(S(I)(e.profit)), 3)
			], 10, fs))), 128))])])]), c(as, {
				pagination: M.value,
				onPageChange: N
			}, null, 8, ["pagination"])]))]);
		};
	}
}), Es = { class: "mb-4 flex items-center justify-between gap-3" }, Ds = { class: "flex items-center gap-2" }, Os = { class: "text-sm font-semibold text-heading" }, ks = { class: "text-sm text-muted" }, As = {
	key: 0,
	class: "py-16 text-center text-sm text-muted"
}, js = { class: "grid grid-cols-1 gap-3 md:grid-cols-7" }, Ms = { class: "mb-2 flex items-center justify-between" }, Ns = { class: "text-xs font-medium text-muted" }, Ps = {
	key: 0,
	class: "rounded-full bg-surface-tertiary px-2 py-0.5 text-xs text-muted"
}, Fs = { class: "flex-1 space-y-2" }, Is = ["onClick"], Ls = { class: "truncate text-xs font-medium text-heading" }, Rs = {
	key: 0,
	class: "font-normal text-primary-600"
}, zs = { class: "mt-0.5 text-xs text-muted" }, Bs = {
	key: 0,
	class: "mt-2 border-t border-line-light pt-1 text-xs text-muted"
}, Vs = {
	key: 0,
	class: "mt-4"
}, Hs = { class: "mb-2 text-xs font-semibold uppercase tracking-wide text-muted" }, Us = { class: "flex flex-wrap gap-2" }, Ws = ["onClick"], Gs = { class: "text-xs font-medium text-heading" }, Ks = { class: "text-xs text-muted" }, qs = /* @__PURE__ */ l({
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
		let o = n, l = v([]), u = v(!0), d = v(M(/* @__PURE__ */ new Date())), p = [
			"Sun",
			"Mon",
			"Tue",
			"Wed",
			"Thu",
			"Fri",
			"Sat"
		], m = t(() => p.map((e, t) => {
			let n = new Date(d.value);
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
		})), g = t(() => l.value.filter((e) => !e.pickup_date)), C = t(() => {
			let e = d.value, t = new Date(e);
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
				l.value = await Me(o.client, co(o.filters));
			} catch (e) {
				o.notify("error", F(e, "Unable to load trips."));
			} finally {
				u.value = !1;
			}
		}
		h(() => {
			E();
		}), w(() => ao(o.filters), () => void E());
		function D() {
			let e = new Date(d.value);
			e.setDate(e.getDate() - 7), d.value = e;
		}
		function O() {
			let e = new Date(d.value);
			e.setDate(e.getDate() + 7), d.value = e;
		}
		function k() {
			d.value = M(/* @__PURE__ */ new Date());
		}
		function A(e) {
			o.router?.push(j.trip(e.id));
		}
		function M(e) {
			let t = new Date(e);
			return t.setHours(0, 0, 0, 0), t.setDate(t.getDate() - t.getDay()), t;
		}
		function N(e) {
			return `${e.getFullYear()}-${String(e.getMonth() + 1).padStart(2, "0")}-${String(e.getDate()).padStart(2, "0")}`;
		}
		return (t, n) => {
			let o = b("BaseButton");
			return _(), i("div", null, [a("div", Es, [a("div", Ds, [
				c(o, {
					size: "sm",
					variant: "white",
					onClick: D
				}, {
					default: T(() => [...n[0] ||= [s("←", -1)]]),
					_: 1
				}),
				a("span", Os, x(C.value), 1),
				c(o, {
					size: "sm",
					variant: "white",
					onClick: O
				}, {
					default: T(() => [...n[1] ||= [s("→", -1)]]),
					_: 1
				}),
				c(o, {
					size: "sm",
					variant: "white",
					onClick: k
				}, {
					default: T(() => [...n[2] ||= [s("Today", -1)]]),
					_: 1
				})
			]), a("span", ks, x(l.value.length) + " trips this week", 1)]), u.value ? (_(), i("div", As, "Loading the week…")) : (_(), i(e, { key: 1 }, [a("div", js, [(_(!0), i(e, null, y(m.value, (t) => (_(), i("div", {
				key: t.dateStr,
				class: f(["flex min-h-48 flex-col rounded-xl border bg-surface p-2", t.isToday ? "border-primary-400" : "border-line-default"])
			}, [
				a("div", Ms, [a("div", null, [a("p", Ns, x(t.name), 1), a("p", { class: f(["text-lg font-semibold", t.isToday ? "text-primary-500" : "text-heading"]) }, x(t.date.getDate()), 3)]), t.trips.length > 0 ? (_(), i("span", Ps, x(t.trips.length), 1)) : r("", !0)]),
				a("div", Fs, [(_(!0), i(e, null, y(t.trips, (e) => (_(), i("div", {
					key: e.id,
					class: "cursor-pointer rounded-lg border border-line-light bg-surface p-2 hover:bg-hover",
					onClick: (t) => A(e)
				}, [a("p", Ls, [
					s(" #" + x(e.trip_no) + " ", 1),
					e.lr_numbers && e.lr_numbers.length > 1 ? (_(), i("span", Rs, " [" + x(e.lr_numbers.join(", ")) + "] ", 1)) : r("", !0),
					s(" · " + x(e.from_city ?? "—") + " → " + x(e.to_city ?? "—"), 1)
				]), a("p", zs, x(e.lorry_no ?? "No lorry") + " · " + x(S(I)(e.revenue)), 1)], 8, Is))), 128))]),
				t.trips.length > 0 ? (_(), i("p", Bs, x(S(I)(t.revenue)), 1)) : r("", !0)
			], 2))), 128))]), g.value.length > 0 ? (_(), i("div", Vs, [a("p", Hs, " Unscheduled (" + x(g.value.length) + ") ", 1), a("div", Us, [(_(!0), i(e, null, y(g.value, (e) => (_(), i("div", {
				key: e.id,
				class: "cursor-pointer rounded-lg border border-line-light bg-surface p-2 hover:bg-hover",
				onClick: (t) => A(e)
			}, [a("p", Gs, "#" + x(e.trip_no), 1), a("p", Ks, x(e.from_city ?? "—") + " → " + x(e.to_city ?? "—"), 1)], 8, Ws))), 128))])])) : r("", !0)], 64))]);
		};
	}
}), Js = { class: "mt-4 flex flex-wrap items-end gap-3" }, Ys = { class: "min-w-44 flex-1" }, Xs = { class: "min-w-44 flex-1" }, Zs = { class: "min-w-44 flex-1" }, Qs = { class: "flex items-center gap-1.5 pb-2.5 text-sm text-body" }, $s = ["checked"], ec = 350, tc = /* @__PURE__ */ l({
	__name: "TripFilters",
	props: {
		modelValue: {},
		statuses: {}
	},
	emits: ["update:modelValue"],
	setup(e, { emit: n }) {
		let o = e, l = n, u = v(o.modelValue.search), d, f = t(() => [{
			id: "",
			label: "All statuses"
		}, ...o.statuses.map((e) => ({
			id: String(e.id),
			label: e.name
		}))]), p = t({
			get: () => g(f.value, o.modelValue.status),
			set: (e) => y({ status: e?.id ?? "" })
		}), h = t(() => io(o.modelValue));
		w(() => o.modelValue.search, (e) => {
			e !== u.value && (u.value = e);
		}), w(u, (e) => {
			clearTimeout(d), d = setTimeout(() => y({ search: e.trim() }), ec);
		}), m(() => clearTimeout(d));
		function g(e, t) {
			return e.find((e) => e.id === t) ?? e[0];
		}
		function y(e) {
			l("update:modelValue", {
				...o.modelValue,
				...e
			});
		}
		function x() {
			y({ unbilled: o.modelValue.unbilled === "1" ? "" : "1" });
		}
		function S() {
			u.value = "", l("update:modelValue", { ...to });
		}
		return (t, n) => {
			let o = b("BaseSelectInput"), l = b("BaseCustomerSelectInput"), d = b("BaseInput");
			return _(), i("div", Js, [
				a("label", Ys, [n[3] ||= a("span", { class: "mb-1 block text-xs font-medium text-muted" }, "Status", -1), c(o, {
					modelValue: p.value,
					"onUpdate:modelValue": n[0] ||= (e) => p.value = e,
					options: f.value,
					"label-key": "label"
				}, null, 8, ["modelValue", "options"])]),
				a("label", Xs, [n[4] ||= a("span", { class: "mb-1 block text-xs font-medium text-muted" }, "Customer", -1), c(l, {
					"model-value": e.modelValue.customer ? Number(e.modelValue.customer) : null,
					"can-deselect": "",
					placeholder: "All customers",
					"onUpdate:modelValue": n[1] ||= (e) => y({ customer: e ? String(e) : "" })
				}, null, 8, ["model-value"])]),
				a("label", Zs, [n[5] ||= a("span", { class: "mb-1 block text-xs font-medium text-muted" }, "Search", -1), c(d, {
					modelValue: u.value,
					"onUpdate:modelValue": n[2] ||= (e) => u.value = e,
					type: "text",
					name: "search",
					autocomplete: "off",
					placeholder: "Search route, lorry, goods..."
				}, null, 8, ["modelValue"])]),
				a("label", Qs, [a("input", {
					type: "checkbox",
					class: "accent-primary-600",
					checked: e.modelValue.unbilled === "1",
					onChange: x
				}, null, 40, $s), n[6] ||= s(" Unbilled only ", -1)]),
				h.value ? (_(), i("button", {
					key: 0,
					type: "button",
					class: "pb-2.5 text-sm font-medium text-primary-500 hover:underline",
					onClick: S
				}, " Clear ")) : r("", !0)
			]);
		};
	}
}), nc = {
	class: "inline-flex overflow-hidden rounded-lg border border-line-default",
	"aria-label": "Trip views"
}, rc = ["aria-current", "onClick"], ic = { class: "max-sm:sr-only" }, ac = /* @__PURE__ */ l({
	__name: "TripViewSwitcher",
	props: {
		active: {},
		query: {}
	},
	emits: ["select"],
	setup(t, { emit: n }) {
		let r = t, o = n, s = [
			{
				id: "board",
				name: M.board,
				label: "Board",
				icon: "ViewColumnsIcon"
			},
			{
				id: "list",
				name: M.list,
				label: "List",
				icon: "ListBulletIcon"
			},
			{
				id: "week",
				name: M.week,
				label: "Week",
				icon: "CalendarDaysIcon"
			}
		];
		function l(e) {
			return r.active === e.name || e.id === "board" && r.active === M.trips;
		}
		function u(e) {
			l(e) || o("select", {
				name: e.name,
				query: r.query
			});
		}
		return (t, n) => {
			let r = b("BaseIcon");
			return _(), i("nav", nc, [(_(), i(e, null, y(s, (e) => a("button", {
				key: e.id,
				type: "button",
				class: f(["flex items-center gap-1.5 border-e border-line-default px-3 py-1.5 text-sm font-medium last:border-e-0", l(e) ? "bg-primary-50 text-primary-500" : "bg-surface text-muted hover:bg-hover hover:text-heading"]),
				"aria-current": l(e) ? "page" : void 0,
				onClick: (t) => u(e)
			}, [c(r, {
				name: e.icon,
				class: "h-4 w-4"
			}, null, 8, ["name"]), a("span", ic, x(e.label), 1)], 10, rc)), 64))]);
		};
	}
}), oc = { class: "flex flex-wrap items-center justify-end gap-3" }, sc = /* @__PURE__ */ l({
	__name: "TripsPage",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {}
	},
	setup(e) {
		let r = e, i = r.router, o = v([]), l = v(!0), u = t(() => r.router.currentRoute.value), d = t(() => no(u.value.query)), p = t(() => ro(d.value)), m = t(() => String(u.value.name ?? ""));
		w(m, (e) => g(e)), h(() => {
			g(m.value), y();
		});
		function g(e) {
			e === M.trips && i.replace({
				name: M.board,
				query: p.value
			});
		}
		async function y() {
			l.value = !0;
			try {
				o.value = await Ne(r.client);
			} catch (e) {
				r.notify("error", F(e, "Unable to load statuses."));
			} finally {
				l.value = !1;
			}
		}
		function x(e) {
			oo(e, d.value) || i.replace({
				name: m.value === M.trips ? M.board : m.value,
				query: ro(e)
			});
		}
		function C(e) {
			i.push(e);
		}
		function E() {
			i.push(j.new);
		}
		return (e, t) => {
			let r = b("BaseBreadcrumbItem"), i = b("BaseBreadcrumb"), u = b("BaseIcon"), h = b("BaseButton"), g = b("router-link"), v = b("BasePageHeader"), y = b("router-view"), w = b("BasePage");
			return _(), n(w, null, {
				default: T(() => [
					c(v, { title: "Trips" }, {
						actions: T(() => [a("div", oc, [
							c(ac, {
								active: m.value,
								query: p.value,
								onSelect: C
							}, null, 8, ["active", "query"]),
							c(g, { to: S(j).reports }, {
								default: T(() => [c(h, { variant: "white" }, {
									left: T((e) => [c(u, {
										name: "ChartBarIcon",
										class: f(e.class)
									}, null, 8, ["class"])]),
									default: T(() => [t[0] ||= s(" Reports ", -1)]),
									_: 1
								})]),
								_: 1
							}, 8, ["to"]),
							c(h, {
								variant: "primary",
								onClick: E
							}, {
								left: T((e) => [c(u, {
									name: "PlusIcon",
									class: f(e.class)
								}, null, 8, ["class"])]),
								default: T(() => [t[1] ||= s(" New Trip ", -1)]),
								_: 1
							})
						])]),
						default: T(() => [c(i, null, {
							default: T(() => [c(r, {
								title: "Dashboard",
								to: "/admin/dashboard"
							}), c(r, {
								title: "Trips",
								to: "#",
								active: ""
							})]),
							_: 1
						})]),
						_: 1
					}),
					c(tc, {
						"model-value": d.value,
						statuses: o.value,
						"onUpdate:modelValue": x
					}, null, 8, ["model-value", "statuses"]),
					c(y, {
						filters: d.value,
						statuses: o.value,
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
}), cc = `${k}:view-trip`;
function lc(e) {
	e.registerPage({
		id: "trips",
		module: k,
		path: "",
		component: N(e, sc),
		meta: {
			ability: cc,
			title: "trips.title"
		},
		children: [
			{
				id: "board",
				module: k,
				path: "",
				component: N(e, Vo),
				meta: {
					ability: cc,
					title: "trips.title"
				}
			},
			{
				id: "list",
				module: k,
				path: "list",
				component: N(e, Ts),
				meta: {
					ability: cc,
					title: "trips.title"
				}
			},
			{
				id: "week",
				module: k,
				path: "week",
				component: N(e, qs),
				meta: {
					ability: cc,
					title: "trips.title"
				}
			}
		]
	}), e.registerPage({
		id: "new",
		module: k,
		path: "new",
		component: N(e, ot),
		meta: {
			ability: cc,
			title: "trips.title"
		}
	}), e.registerPage({
		id: "trip",
		module: k,
		path: ":id(\\d+)",
		component: N(e, Qr),
		meta: {
			ability: cc,
			title: "trips.title"
		}
	});
}
//#endregion
//#region resources/js/init.ts
window.InvoiceShelf.booting((e, t, n) => {
	n.addMessages(O), lc(n), je(n);
});
//#endregion
