const { Fragment: e, Teleport: t, Transition: n, computed: r, createBlock: i, createCommentVNode: a, createElementBlock: o, createElementVNode: s, createTextVNode: c, createVNode: l, defineComponent: u, nextTick: d, normalizeStyle: f, onBeforeUnmount: p, onMounted: m, openBlock: h, reactive: g, ref: _, renderList: v, resolveComponent: y, shallowRef: b, toDisplayString: x, vModelText: S, watch: ee, withCtx: C, withDirectives: w, withModifiers: T } = window.__invoiceshelf_vue;
//#region resources/js/components/LorryPartySelectPopup.vue?vue&type=script&setup=true&lang.ts
var E = ["aria-label"], D = { class: "flex items-start gap-3" }, te = {
	class: "flex items-center justify-center w-11 h-11 text-sm font-semibold rounded-xl shrink-0 bg-btn-primary text-on-primary",
	"aria-hidden": "true"
}, ne = { class: "flex-1 min-w-0" }, re = { class: "text-xs font-medium text-muted" }, ie = { class: "text-base font-semibold truncate text-heading" }, ae = {
	key: 0,
	class: "text-sm truncate text-muted"
}, oe = { class: "flex items-center gap-1 -me-1 shrink-0" }, se = ["aria-label"], ce = ["aria-label"], le = { key: 1 }, ue = ["aria-expanded"], de = { class: "flex flex-col flex-1 min-w-0" }, fe = { class: "text-base font-semibold text-heading" }, pe = ["aria-label"], me = { class: "relative" }, he = { class: "flex flex-col overflow-y-auto border-t border-line-light max-h-80 overscroll-contain" }, ge = ["onClick"], _e = {
	class: "flex items-center justify-center w-10 h-10 text-sm font-semibold rounded-xl shrink-0 bg-primary-50 text-primary-700",
	"aria-hidden": "true"
}, ve = { class: "flex flex-col min-w-0" }, ye = { class: "flex items-center gap-2" }, be = { class: "text-sm font-medium truncate text-heading" }, xe = { class: "text-xs px-2 py-0.5 rounded-full bg-primary-100 text-primary-700 font-medium" }, Se = {
	key: 0,
	class: "text-sm truncate text-muted"
}, Ce = ["title", "onClick"], we = {
	class: "flex items-center justify-center w-10 h-10 text-sm font-semibold rounded-xl shrink-0 bg-surface-secondary text-muted",
	"aria-hidden": "true"
}, Te = { class: "flex flex-col min-w-0" }, Ee = { class: "flex items-center gap-2" }, De = { class: "text-sm font-medium truncate text-heading" }, Oe = {
	key: 0,
	class: "text-sm truncate text-muted"
}, ke = {
	key: 0,
	class: "px-4 py-8 text-sm text-center text-muted",
	role: "status"
}, Ae = {
	key: 1,
	class: "flex flex-col gap-1 px-4 py-8 text-sm text-center",
	role: "status"
}, je = {
	key: 0,
	class: "text-muted"
}, Me = { class: "font-medium text-heading" }, Ne = { class: "w-full max-w-md p-6 bg-surface rounded-xl" }, Pe = { class: "mb-4 text-lg font-semibold text-heading" }, Fe = { class: "space-y-3" }, Ie = { class: "mt-3 text-xs text-muted" }, Le = { class: "flex gap-2 mt-4" }, Re = { class: "w-full max-w-md p-6 bg-surface rounded-xl max-h-[90vh] overflow-y-auto" }, ze = { class: "mb-4 text-lg font-semibold text-heading" }, Be = { class: "space-y-3" }, Ve = { class: "flex gap-2 mt-4" }, O = /* @__PURE__ */ u({
	__name: "LorryPartySelectPopup",
	props: {
		client: { type: [Function, Object] },
		type: {},
		label: {},
		modelValue: {}
	},
	emits: ["select", "clear"],
	setup(u, { emit: y }) {
		let b = u, O = y, k = _(!1), A = _(null), j = _([]), M = _([]), N = _(!1), P = _(b.modelValue ?? null), F = _(!1), I = _({
			name: "",
			phone: "",
			email: "",
			address: "",
			bank_account_no: "",
			licence_no: "",
			pan_number: ""
		}), L = _(null), R = _(null), z = _(null), B = _(null), V = g({
			top: "0px",
			left: "0px",
			width: "auto"
		});
		function H(e) {
			return e.split(" ").map((e) => e.charAt(0)).slice(0, 2).join("").toUpperCase();
		}
		async function U() {
			N.value = !0;
			try {
				let [e, t] = await Promise.all([b.client.get("/api/v1/lorry-receipts/lorry-party-profiles", { params: {
					type: b.type,
					search: A.value,
					limit: "all"
				} }), b.client.get("/api/v1/suppliers", { params: { limit: 100 } }).catch(() => null)]);
				j.value = e.data.data || [], M.value = t?.data?.data || [];
			} catch {
				j.value = [], M.value = [];
			} finally {
				N.value = !1;
			}
		}
		let W = r(() => {
			if (!A.value) return [];
			let e = new Set(j.value.map((e) => e.supplier_id).filter(Boolean)), t = A.value.toLowerCase();
			return M.value.filter((n) => !e.has(n.id) && (n.name.toLowerCase().includes(t) || (n.phone || "").includes(t)));
		});
		async function He(e) {
			try {
				let { data: t } = await b.client.post("/api/v1/lorry-receipts/lorry-party-profiles", {
					type: b.type,
					supplier_id: e.id,
					name: e.name,
					phone: e.phone ?? ""
				});
				t?.data && Y(t.data);
			} catch (e) {
				console.error("Failed to create profile from supplier", e);
			}
		}
		let G;
		function Ue() {
			clearTimeout(G), G = setTimeout(() => U(), 400);
		}
		function K() {
			if (!L.value) return;
			let e = L.value.getBoundingClientRect();
			V.top = e.bottom + window.scrollY + 8 + "px", V.left = e.left + window.scrollX + "px", V.width = e.width + "px";
		}
		function q() {
			k.value = !0, U(), d(() => {
				K(), B.value?.querySelector("input")?.focus();
			});
		}
		async function J() {
			let e = k.value;
			k.value = !1, e && (await d(), (z.value ?? L.value)?.focus());
		}
		function Y(e) {
			P.value = e, k.value = !1, A.value = null, O("select", e);
		}
		function We() {
			P.value = null, O("clear");
		}
		function Ge() {
			k.value = !1, I.value = {
				name: "",
				phone: "",
				email: "",
				address: "",
				bank_account_no: "",
				licence_no: "",
				pan_number: ""
			}, F.value = !0;
		}
		async function Ke() {
			try {
				let e = {
					type: b.type,
					name: I.value.name,
					phone: I.value.phone,
					email: I.value.email || null,
					address: I.value.address,
					bank_account_no: I.value.bank_account_no
				};
				b.type === "DRIVER" && (e.licence_no = I.value.licence_no), (b.type === "OWNER" || b.type === "BROKER") && (e.pan_number = I.value.pan_number);
				let { data: t } = await b.client.post("/api/v1/lorry-receipts/lorry-party-profiles", e);
				F.value = !1, t?.data && Y(t.data);
			} catch (e) {
				console.error("Failed to create profile", e);
			}
		}
		let qe = r(() => {
			let e = window;
			return typeof e.__lorryReceiptCan == "function" ? !!e.__lorryReceiptCan("lorry-receipt:manage-party-profiles") : !0;
		}), X = _(!1), Z = _({
			name: "",
			phone: "",
			address: "",
			bank_account_no: "",
			pan_number: "",
			licence_no: "",
			licence_date: "",
			rto_address: "",
			valid_up_to: ""
		});
		function Je() {
			let e = P.value;
			e && (Z.value = {
				name: e.name ?? "",
				phone: e.phone ?? "",
				address: e.address ?? "",
				bank_account_no: e.bank_account_no ?? "",
				pan_number: e.pan_number ?? "",
				licence_no: e.licence_no ?? "",
				licence_date: e.licence_date ?? "",
				rto_address: e.rto_address ?? "",
				valid_up_to: e.valid_up_to ?? ""
			}, X.value = !0);
		}
		async function Ye() {
			let e = P.value;
			if (!(!e || !e.id)) try {
				let { data: t } = await b.client.put(`/api/v1/lorry-receipts/lorry-party-profiles/${e.id}`, {
					type: b.type,
					...Z.value
				});
				X.value = !1, t?.data && (U(), Y(t.data));
			} catch (e) {
				console.error("Failed to update profile", e);
			}
		}
		function Xe(e) {
			if (!k.value) return;
			let t = e.target;
			R.value?.contains(t) || L.value?.contains(t) || J();
		}
		function Q(e) {
			e.key === "Escape" && k.value && J();
		}
		function $() {
			k.value && K();
		}
		return m(() => {
			document.addEventListener("click", Xe), document.addEventListener("keydown", Q), window.addEventListener("scroll", $, !0), window.addEventListener("resize", $);
		}), p(() => {
			document.removeEventListener("click", Xe), document.removeEventListener("keydown", Q), window.removeEventListener("scroll", $, !0), window.removeEventListener("resize", $);
		}), ee(() => b.modelValue, (e) => {
			P.value = e ?? null;
		}), (r, d) => (h(), o("div", null, [
			P.value ? (h(), o("div", {
				key: 0,
				ref_key: "card",
				ref: z,
				tabindex: "-1",
				"aria-label": u.label + ": " + P.value.name,
				class: "flex flex-col gap-4 p-4 border md:p-5 glass rounded-xl focus:outline-hidden focus-visible:ring-2 focus-visible:ring-focus"
			}, [s("div", D, [
				s("span", te, x(H(P.value.name)), 1),
				s("div", ne, [
					s("p", re, x(u.label), 1),
					s("p", ie, x(P.value.name), 1),
					P.value.phone || P.value.address ? (h(), o("p", ae, x(P.value.phone || P.value.address), 1)) : a("", !0)
				]),
				s("div", oe, [P.value.id && qe.value ? (h(), o("button", {
					key: 0,
					type: "button",
					class: "flex items-center justify-center w-10 h-10 transition-colors rounded-lg md:w-9 md:h-9 text-muted hover:bg-hover-strong hover:text-heading",
					"aria-label": "Edit " + u.label,
					onClick: Je
				}, [...d[22] ||= [s("svg", {
					class: "w-5 h-5",
					fill: "none",
					stroke: "currentColor",
					viewBox: "0 0 24 24"
				}, [s("path", {
					"stroke-linecap": "round",
					"stroke-linejoin": "round",
					"stroke-width": "2",
					d: "M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
				})], -1)]], 8, se)) : a("", !0), s("button", {
					type: "button",
					class: "flex items-center justify-center w-10 h-10 transition-colors rounded-lg md:w-9 md:h-9 text-muted hover:bg-hover-strong hover:text-heading",
					"aria-label": "Change " + u.label,
					onClick: We
				}, [...d[23] ||= [s("svg", {
					class: "w-5 h-5",
					fill: "none",
					stroke: "currentColor",
					viewBox: "0 0 24 24"
				}, [s("path", {
					"stroke-linecap": "round",
					"stroke-linejoin": "round",
					"stroke-width": "2",
					d: "M6 18L18 6M6 6l12 12"
				})], -1)]], 8, ce)])
			])], 8, E)) : (h(), o("div", le, [s("button", {
				ref_key: "trigger",
				ref: L,
				type: "button",
				"aria-expanded": k.value,
				"aria-haspopup": "dialog",
				class: "flex items-center w-full gap-4 p-4 text-start transition-colors border-2 border-dashed md:p-5 rounded-xl bg-surface/50 hover:border-primary-400 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-focus",
				onClick: d[0] ||= (e) => k.value ? J() : q()
			}, [
				d[25] ||= s("span", {
					class: "flex items-center justify-center w-11 h-11 rounded-xl shrink-0 bg-primary-50 text-primary-600",
					"aria-hidden": "true"
				}, [s("svg", {
					class: "w-5 h-5",
					fill: "none",
					stroke: "currentColor",
					viewBox: "0 0 24 24"
				}, [s("path", {
					"stroke-linecap": "round",
					"stroke-linejoin": "round",
					"stroke-width": "2",
					d: "M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
				})])], -1),
				s("span", de, [s("span", fe, x(u.label), 1), d[24] ||= s("span", { class: "text-sm text-muted" }, "Click to select", -1)]),
				d[26] ||= s("svg", {
					class: "w-5 h-5 text-subtle shrink-0",
					fill: "none",
					stroke: "currentColor",
					viewBox: "0 0 24 24"
				}, [s("path", {
					"stroke-linecap": "round",
					"stroke-linejoin": "round",
					"stroke-width": "2",
					d: "M19 9l-7 7-7-7"
				})], -1)
			], 8, ue)])),
			(h(), i(t, { to: "body" }, [l(n, {
				"enter-active-class": "transition duration-150 ease-out",
				"enter-from-class": "translate-y-1 opacity-0",
				"leave-active-class": "transition duration-100 ease-in",
				"leave-to-class": "translate-y-1 opacity-0"
			}, {
				default: C(() => [k.value ? (h(), o("div", {
					key: 0,
					ref_key: "panel",
					ref: R,
					role: "dialog",
					"aria-label": u.label,
					style: f({
						position: "absolute",
						top: V.top,
						left: V.left,
						width: V.width,
						zIndex: 9999
					}),
					class: "overflow-hidden border glass-strong rounded-xl shadow-xl"
				}, [
					s("div", {
						ref_key: "searchField",
						ref: B,
						class: "p-3"
					}, [s("div", me, [d[27] ||= s("svg", {
						class: "absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-subtle",
						fill: "none",
						stroke: "currentColor",
						viewBox: "0 0 24 24"
					}, [s("path", {
						"stroke-linecap": "round",
						"stroke-linejoin": "round",
						"stroke-width": "2",
						d: "M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
					})], -1), w(s("input", {
						"onUpdate:modelValue": d[1] ||= (e) => A.value = e,
						type: "search",
						placeholder: "Search...",
						class: "w-full py-2 pl-9 pr-3 text-sm border border-line-default rounded-lg bg-surface text-heading placeholder:text-subtle focus:outline-none focus:ring-2 focus:ring-focus",
						onInput: Ue
					}, null, 544), [[S, A.value]])])], 512),
					s("ul", he, [
						(h(!0), o(e, null, v(j.value, (e) => (h(), o("li", { key: "p" + e.id }, [s("button", {
							type: "button",
							class: "flex items-center w-full gap-3 px-4 py-3 text-start transition-colors hover:bg-hover-strong focus:outline-hidden focus-visible:bg-hover-strong",
							onClick: (t) => Y(e)
						}, [s("span", _e, x(H(e.name)), 1), s("span", ve, [s("span", ye, [s("span", be, x(e.name), 1), s("span", xe, x(e.type), 1)]), e.phone || e.address ? (h(), o("span", Se, x(e.phone || e.address), 1)) : a("", !0)])], 8, ge)]))), 128)),
						(h(!0), o(e, null, v(W.value, (e) => (h(), o("li", { key: "s" + e.id }, [s("button", {
							type: "button",
							class: "flex items-center w-full gap-3 px-4 py-3 text-start transition-colors hover:bg-hover-strong focus:outline-hidden focus-visible:bg-hover-strong",
							title: "Use this supplier as the " + u.label,
							onClick: (t) => He(e)
						}, [s("span", we, x(H(e.name)), 1), s("span", Te, [s("span", Ee, [s("span", De, x(e.name), 1), d[28] ||= s("span", { class: "text-xs px-2 py-0.5 rounded-full bg-surface-secondary text-muted font-medium" }, " Supplier ", -1)]), e.phone || e.email ? (h(), o("span", Oe, x(e.phone || e.email), 1)) : a("", !0)])], 8, Ce)]))), 128)),
						N.value ? (h(), o("li", ke, " Loading... ")) : j.value.length === 0 && W.value.length === 0 ? (h(), o("li", Ae, [A.value ? (h(), o("span", je, "No matches found")) : (h(), o(e, { key: 1 }, [s("span", Me, "No " + x(u.label) + " profiles yet", 1), d[29] ||= s("span", { class: "text-muted" }, "Add one to get started", -1)], 64))])) : a("", !0)
					]),
					qe.value ? (h(), o("button", {
						key: 0,
						type: "button",
						class: "flex items-center justify-center w-full gap-2 text-sm font-medium transition-colors border-t h-12 border-line-light text-primary-600 hover:bg-primary-50/60",
						onClick: Ge
					}, [d[30] ||= s("svg", {
						class: "w-5 h-5",
						fill: "none",
						stroke: "currentColor",
						viewBox: "0 0 24 24"
					}, [s("path", {
						"stroke-linecap": "round",
						"stroke-linejoin": "round",
						"stroke-width": "2",
						d: "M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
					})], -1), c(" Add New " + x(u.label), 1)])) : a("", !0)
				], 12, pe)) : a("", !0)]),
				_: 1
			})])),
			(h(), i(t, { to: "body" }, [F.value ? (h(), o("div", {
				key: 0,
				class: "fixed inset-0 z-[10000] flex items-center justify-center bg-black/50",
				onClick: d[10] ||= T((e) => F.value = !1, ["self"])
			}, [s("div", Ne, [
				s("h3", Pe, "New " + x(u.label), 1),
				s("div", Fe, [
					w(s("input", {
						"onUpdate:modelValue": d[2] ||= (e) => I.value.name = e,
						placeholder: "Name *",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[S, I.value.name]]),
					w(s("input", {
						"onUpdate:modelValue": d[3] ||= (e) => I.value.phone = e,
						placeholder: "Phone",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[S, I.value.phone]]),
					w(s("input", {
						"onUpdate:modelValue": d[4] ||= (e) => I.value.email = e,
						type: "email",
						placeholder: "Email",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[S, I.value.email]]),
					w(s("textarea", {
						"onUpdate:modelValue": d[5] ||= (e) => I.value.address = e,
						placeholder: "Address",
						rows: "2",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[S, I.value.address]]),
					w(s("input", {
						"onUpdate:modelValue": d[6] ||= (e) => I.value.bank_account_no = e,
						placeholder: "Bank Account No",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[S, I.value.bank_account_no]]),
					u.type === "DRIVER" ? w((h(), o("input", {
						key: 0,
						"onUpdate:modelValue": d[7] ||= (e) => I.value.licence_no = e,
						placeholder: "Licence No",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512)), [[S, I.value.licence_no]]) : a("", !0),
					u.type === "OWNER" || u.type === "BROKER" ? w((h(), o("input", {
						key: 1,
						"onUpdate:modelValue": d[8] ||= (e) => I.value.pan_number = e,
						placeholder: "PAN No",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512)), [[S, I.value.pan_number]]) : a("", !0)
				]),
				s("p", Ie, " Also saved as a Supplier, so bills and payments can be tracked against this " + x(u.label) + ". ", 1),
				s("div", Le, [s("button", {
					type: "button",
					class: "flex-1 px-4 py-2 text-sm font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700",
					onClick: Ke
				}, "Save"), s("button", {
					type: "button",
					class: "px-4 py-2 text-sm border border-line-default rounded-lg text-heading",
					onClick: d[9] ||= (e) => F.value = !1
				}, "Cancel")])
			])])) : a("", !0)])),
			(h(), i(t, { to: "body" }, [X.value ? (h(), o("div", {
				key: 0,
				class: "fixed inset-0 z-[10000] flex items-center justify-center bg-black/50",
				onClick: d[21] ||= T((e) => X.value = !1, ["self"])
			}, [s("div", Re, [
				s("h3", ze, "Edit " + x(u.label), 1),
				s("div", Be, [
					w(s("input", {
						"onUpdate:modelValue": d[11] ||= (e) => Z.value.name = e,
						placeholder: "Name *",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[S, Z.value.name]]),
					w(s("input", {
						"onUpdate:modelValue": d[12] ||= (e) => Z.value.phone = e,
						placeholder: "Phone",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[S, Z.value.phone]]),
					w(s("textarea", {
						"onUpdate:modelValue": d[13] ||= (e) => Z.value.address = e,
						placeholder: "Address",
						rows: "2",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[S, Z.value.address]]),
					w(s("input", {
						"onUpdate:modelValue": d[14] ||= (e) => Z.value.bank_account_no = e,
						placeholder: "Bank Account No",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[S, Z.value.bank_account_no]]),
					u.type === "OWNER" || u.type === "BROKER" ? w((h(), o("input", {
						key: 0,
						"onUpdate:modelValue": d[15] ||= (e) => Z.value.pan_number = e,
						placeholder: "PAN No",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512)), [[S, Z.value.pan_number]]) : a("", !0),
					u.type === "DRIVER" ? (h(), o(e, { key: 1 }, [
						w(s("input", {
							"onUpdate:modelValue": d[16] ||= (e) => Z.value.licence_no = e,
							placeholder: "Licence No",
							class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
						}, null, 512), [[S, Z.value.licence_no]]),
						w(s("input", {
							"onUpdate:modelValue": d[17] ||= (e) => Z.value.licence_date = e,
							type: "date",
							placeholder: "Licence Date",
							class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
						}, null, 512), [[S, Z.value.licence_date]]),
						w(s("input", {
							"onUpdate:modelValue": d[18] ||= (e) => Z.value.rto_address = e,
							placeholder: "RTO",
							class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
						}, null, 512), [[S, Z.value.rto_address]]),
						w(s("input", {
							"onUpdate:modelValue": d[19] ||= (e) => Z.value.valid_up_to = e,
							type: "date",
							placeholder: "Valid Up To",
							class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
						}, null, 512), [[S, Z.value.valid_up_to]])
					], 64)) : a("", !0)
				]),
				s("div", Ve, [s("button", {
					type: "button",
					class: "flex-1 px-4 py-2 text-sm font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700",
					onClick: Ye
				}, "Save"), s("button", {
					type: "button",
					class: "px-4 py-2 text-sm border border-line-default rounded-lg text-heading",
					onClick: d[20] ||= (e) => X.value = !1
				}, "Cancel")])
			])])) : a("", !0)]))
		]));
	}
}), k = {
	key: 0,
	class: "space-y-6 mt-5"
}, A = { class: "grid gap-4 grid-cols-1 md:grid-cols-3" }, j = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, M = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, N = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, P = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, F = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, I = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, L = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, R = /* @__PURE__ */ u({
	__name: "LorryReceiptFormFields",
	props: {
		templateName: {},
		store: {}
	},
	setup(e) {
		let t = e, n = r(() => t.store?.newInvoice), i = r(() => t.templateName === "lorry_receipt"), c = b(null);
		function u() {
			if (c.value) return c.value;
			let e = window;
			if (e.__lorryReceiptClient) return c.value = e.__lorryReceiptClient, c.value;
			throw Error("API client not initialized for LorryReceipt module");
		}
		let d = ["UMB", "VAPI"], f = _([]);
		async function p() {
			try {
				let { data: e } = await u().get("/api/v1/payment-methods", { params: { limit: "all" } });
				f.value = e?.data ?? [];
			} catch {
				f.value = [];
			}
		}
		let g = _([]);
		async function v() {
			try {
				let { data: e } = await u().get("/api/v1/lorry-receipts/banks");
				g.value = e?.data ?? [];
			} catch {
				g.value = [];
			}
		}
		let x = [
			["tr_owner_name", "name"],
			["tr_owner_address", "address"],
			["tr_owner_phone", "phone"],
			["tr_owner_bank_account_no", "bank_account_no"],
			["tr_owner_pan_no", "pan_number"]
		], S = [
			["tr_driver_name", "name"],
			["tr_driver_address", "address"],
			["tr_driver_licence_no", "licence_no"],
			["tr_driver_licence_date", "licence_date"],
			["tr_driver_rto_address", "rto_address"],
			["tr_driver_valid_up_to", "valid_up_to"],
			["tr_driver_bank_account_no", "bank_account_no"]
		], w = [
			["tr_broker_name", "name"],
			["tr_broker_address", "address"],
			["tr_broker_pan_no", "pan_number"],
			["tr_advice_date", "advice_date"],
			["tr_broker_phone", "phone"],
			["tr_broker_bank_account_no", "bank_account_no"]
		];
		function T(e, t) {
			n.value && e.forEach(([e, r]) => {
				n.value[e] = t?.[r] || "";
			});
		}
		function E(e) {
			if (!n.value) return null;
			let t = n.value, r = {};
			for (let [n, i] of e) r[i] = t[n] ?? "";
			return r.name ? {
				id: 0,
				type: "",
				...r
			} : null;
		}
		function D(e, t) {
			let n = E(e);
			if (!n) return null;
			let r = Number(t) || 0;
			return r ? {
				...n,
				id: r
			} : n;
		}
		let te = r(() => D(x, n.value?.tr_owner_profile_id)), ne = r(() => D(S, n.value?.tr_driver_profile_id)), re = r(() => D(w, n.value?.tr_broker_profile_id));
		function ie(e) {
			n.value && (n.value.tr_owner_profile_id = e.id), T(x, e), n.value && (n.value.tr_paid_to = e.name, n.value.tr_final_paid_to = e.name);
		}
		function ae() {
			n.value && (n.value.tr_owner_profile_id = null), T(x, null);
		}
		function oe(e) {
			n.value && (n.value.tr_driver_profile_id = e.id), T(S, e);
		}
		function se() {
			n.value && (n.value.tr_driver_profile_id = null), T(S, null);
		}
		function ce(e) {
			n.value && (n.value.tr_broker_profile_id = e.id), T(w, e);
		}
		function le() {
			n.value && (n.value.tr_broker_profile_id = null), T(w, null);
		}
		function ue() {
			if (!n.value) return;
			let e = Number(n.value.tr_lorry_hire_amount) || 0, t = Number(n.value.tr_other_charges_amount) || 0, r = Number(n.value.tr_advance_amount) || 0, i = Number(n.value.tr_detention_amount) || 0, a = Number(n.value.tr_extra_hire_amount) || 0, o = Number(n.value.tr_final_other_amount) || 0, s = Number(n.value.tr_less_advance_other_branch_amount) || 0, c = Number(n.value.tr_less_deduction_claims_amount) || 0, l = e + t + i + a + o - r - s - c;
			n.value.tr_net_amount_payable = String(l), n.value.sub_total = Math.round(l * 100), n.value.total = Math.round(l * 100);
		}
		return ee(() => [
			n.value?.tr_lorry_hire_amount,
			n.value?.tr_other_charges_amount,
			n.value?.tr_advance_amount,
			n.value?.tr_detention_amount,
			n.value?.tr_extra_hire_amount,
			n.value?.tr_final_other_amount,
			n.value?.tr_less_advance_other_branch_amount,
			n.value?.tr_less_deduction_claims_amount
		], () => ue()), m(() => {
			p(), v();
		}), (e, t) => {
			let r = y("BaseCard"), c = y("BaseInput"), p = y("BaseInputGroup"), m = y("BaseSelectInput"), _ = y("BaseTextarea");
			return i.value && n.value ? (h(), o("div", k, [
				l(r, { class: "mb-4" }, {
					header: C(() => [...t[52] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Select Party (Owner / Driver / Broker)", -1)]]),
					default: C(() => [t[53] ||= s("p", { class: "text-sm text-muted mb-4" }, "Select a party profile to auto-fill the Owner, Driver, and Broker details below.", -1), s("div", A, [
						l(O, {
							client: u(),
							type: "OWNER",
							label: "Owner",
							"model-value": te.value,
							onSelect: ie,
							onClear: ae
						}, null, 8, ["client", "model-value"]),
						l(O, {
							client: u(),
							type: "DRIVER",
							label: "Driver",
							"model-value": ne.value,
							onSelect: oe,
							onClear: se
						}, null, 8, ["client", "model-value"]),
						l(O, {
							client: u(),
							type: "BROKER",
							label: "Broker",
							"model-value": re.value,
							onSelect: ce,
							onClear: le
						}, null, 8, ["client", "model-value"])
					])]),
					_: 1
				}),
				l(r, { class: "mb-4" }, {
					header: C(() => [...t[54] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Trip Details", -1)]]),
					default: C(() => [s("div", j, [
						l(p, { label: "From" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_from_code,
								"onUpdate:modelValue": t[0] ||= (e) => n.value.tr_from_code = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "To" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_to_code,
								"onUpdate:modelValue": t[1] ||= (e) => n.value.tr_to_code = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "No Of Pages" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_no_of_pages,
								"onUpdate:modelValue": t[2] ||= (e) => n.value.tr_no_of_pages = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "No Of Packages" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_no_of_packages,
								"onUpdate:modelValue": t[3] ||= (e) => n.value.tr_no_of_packages = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Actual Weight" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_actual_weight,
								"onUpdate:modelValue": t[4] ||= (e) => n.value.tr_actual_weight = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Charge Weight" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_charged_weight,
								"onUpdate:modelValue": t[5] ||= (e) => n.value.tr_charged_weight = e
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				}),
				l(r, { class: "mb-4" }, {
					header: C(() => [...t[55] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Vehicle Details", -1)]]),
					default: C(() => [s("div", M, [
						l(p, { label: "Lorry No" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_lorry_no,
								"onUpdate:modelValue": t[6] ||= (e) => n.value.tr_lorry_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Regd at" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_regd_at,
								"onUpdate:modelValue": t[7] ||= (e) => n.value.tr_regd_at = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Body Type" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_body_type,
								"onUpdate:modelValue": t[8] ||= (e) => n.value.tr_body_type = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Make" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_make,
								"onUpdate:modelValue": t[9] ||= (e) => n.value.tr_make = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Model" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_vehicle_model,
								"onUpdate:modelValue": t[10] ||= (e) => n.value.tr_vehicle_model = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Colour" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_colour,
								"onUpdate:modelValue": t[11] ||= (e) => n.value.tr_colour = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Chasis No" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_chasis_no,
								"onUpdate:modelValue": t[12] ||= (e) => n.value.tr_chasis_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Engine No" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_engine_no,
								"onUpdate:modelValue": t[13] ||= (e) => n.value.tr_engine_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				}),
				l(r, { class: "mb-4" }, {
					header: C(() => [...t[56] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Hire Particulars", -1)]]),
					default: C(() => [s("div", N, [
						l(p, { label: "Paid To" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_paid_to,
								"onUpdate:modelValue": t[14] ||= (e) => n.value.tr_paid_to = e,
								placeholder: "Auto-filled from Owner selection"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Lorry Hire" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_lorry_hire_amount,
								"onUpdate:modelValue": t[15] ||= (e) => n.value.tr_lorry_hire_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Add Other Charges" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_other_charges_amount,
								"onUpdate:modelValue": t[16] ||= (e) => n.value.tr_other_charges_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Advance Payment Mode" }, {
							default: C(() => [l(m, {
								modelValue: n.value.tr_advance_payment_method_id,
								"onUpdate:modelValue": t[17] ||= (e) => n.value.tr_advance_payment_method_id = e,
								options: f.value.map((e) => ({
									id: e.id,
									label: e.name
								})),
								"value-prop": "id",
								"label-key": "label",
								placeholder: "Select Payment Mode"
							}, null, 8, ["modelValue", "options"])]),
							_: 1
						}),
						l(p, { label: "Advance On" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_advance_on,
								"onUpdate:modelValue": t[18] ||= (e) => n.value.tr_advance_on = e,
								type: "date"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Bank" }, {
							default: C(() => [l(m, {
								modelValue: n.value.tr_advance_bank,
								"onUpdate:modelValue": t[19] ||= (e) => n.value.tr_advance_bank = e,
								options: g.value.map((e) => ({
									id: e.name,
									label: e.name
								})),
								"value-prop": "id",
								"label-key": "label",
								placeholder: "Select Bank"
							}, null, 8, ["modelValue", "options"])]),
							_: 1
						}),
						l(p, { label: "Advance Paid Rs" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_advance_amount,
								"onUpdate:modelValue": t[20] ||= (e) => n.value.tr_advance_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Balance Payable at" }, {
							default: C(() => [l(m, {
								modelValue: n.value.tr_balance_payable_at,
								"onUpdate:modelValue": t[21] ||= (e) => n.value.tr_balance_payable_at = e,
								options: d.map((e) => ({
									id: e,
									label: e
								})),
								"value-prop": "id",
								"label-key": "label",
								placeholder: "Select..."
							}, null, 8, ["modelValue", "options"])]),
							_: 1
						}),
						l(p, { label: "Loaded By" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_loaded_by,
								"onUpdate:modelValue": t[22] ||= (e) => n.value.tr_loaded_by = e
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				}),
				l(r, { class: "mb-4" }, {
					header: C(() => [...t[57] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Final Payment Details", -1)]]),
					default: C(() => [s("div", P, [
						l(p, { label: "Final Paid To" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_final_paid_to,
								"onUpdate:modelValue": t[23] ||= (e) => n.value.tr_final_paid_to = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Add Detention Rs." }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_detention_amount,
								"onUpdate:modelValue": t[24] ||= (e) => n.value.tr_detention_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Extra Hire Rs" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_extra_hire_amount,
								"onUpdate:modelValue": t[25] ||= (e) => n.value.tr_extra_hire_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Other Rs" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_final_other_amount,
								"onUpdate:modelValue": t[26] ||= (e) => n.value.tr_final_other_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Less Adv. at other branch" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_less_advance_other_branch_amount,
								"onUpdate:modelValue": t[27] ||= (e) => n.value.tr_less_advance_other_branch_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Less Deduction for Claims" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_less_deduction_claims_amount,
								"onUpdate:modelValue": t[28] ||= (e) => n.value.tr_less_deduction_claims_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Final Balance Amount Paid at" }, {
							default: C(() => [l(m, {
								modelValue: n.value.tr_final_balance_paid_at,
								"onUpdate:modelValue": t[29] ||= (e) => n.value.tr_final_balance_paid_at = e,
								options: d.map((e) => ({
									id: e,
									label: e
								})),
								"value-prop": "id",
								"label-key": "label",
								placeholder: "Select..."
							}, null, 8, ["modelValue", "options"])]),
							_: 1
						}),
						l(p, { label: "Final Balance Date" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_final_balance_on,
								"onUpdate:modelValue": t[30] ||= (e) => n.value.tr_final_balance_on = e,
								type: "date"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Final Payment Mode" }, {
							default: C(() => [l(m, {
								modelValue: n.value.tr_final_payment_method_id,
								"onUpdate:modelValue": t[31] ||= (e) => n.value.tr_final_payment_method_id = e,
								options: f.value.map((e) => ({
									id: e.id,
									label: e.name
								})),
								"value-prop": "id",
								"label-key": "label",
								placeholder: "Select Payment Mode"
							}, null, 8, ["modelValue", "options"])]),
							_: 1
						}),
						l(p, { label: "Final Bank" }, {
							default: C(() => [l(m, {
								modelValue: n.value.tr_final_bank,
								"onUpdate:modelValue": t[32] ||= (e) => n.value.tr_final_bank = e,
								options: g.value.map((e) => ({
									id: e.name,
									label: e.name
								})),
								"value-prop": "id",
								"label-key": "label",
								placeholder: "Select Bank"
							}, null, 8, ["modelValue", "options"])]),
							_: 1
						}),
						l(p, { label: "Net Amount Payable" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_net_amount_payable,
								"onUpdate:modelValue": t[33] ||= (e) => n.value.tr_net_amount_payable = e,
								type: "number",
								readonly: ""
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				}),
				l(r, { class: "mb-4" }, {
					header: C(() => [...t[58] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Owner Details", -1)]]),
					default: C(() => [s("div", F, [
						l(p, { label: "Owner Name" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_owner_name,
								"onUpdate:modelValue": t[34] ||= (e) => n.value.tr_owner_name = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Owner Phone No" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_owner_phone,
								"onUpdate:modelValue": t[35] ||= (e) => n.value.tr_owner_phone = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Owner Bank Account No" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_owner_bank_account_no,
								"onUpdate:modelValue": t[36] ||= (e) => n.value.tr_owner_bank_account_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Owner PAN No" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_owner_pan_no,
								"onUpdate:modelValue": t[37] ||= (e) => n.value.tr_owner_pan_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, {
							label: "Owner Address",
							class: "md:col-span-2 xl:col-span-3"
						}, {
							default: C(() => [l(_, {
								modelValue: n.value.tr_owner_address,
								"onUpdate:modelValue": t[38] ||= (e) => n.value.tr_owner_address = e,
								rows: "2"
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				}),
				l(r, { class: "mb-4" }, {
					header: C(() => [...t[59] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Driver Details", -1)]]),
					default: C(() => [s("div", I, [
						l(p, { label: "Driver Name" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_driver_name,
								"onUpdate:modelValue": t[39] ||= (e) => n.value.tr_driver_name = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Driver Licence No" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_driver_licence_no,
								"onUpdate:modelValue": t[40] ||= (e) => n.value.tr_driver_licence_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Driver Licence Date" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_driver_licence_date,
								"onUpdate:modelValue": t[41] ||= (e) => n.value.tr_driver_licence_date = e,
								type: "date"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Driver RTO" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_driver_rto_address,
								"onUpdate:modelValue": t[42] ||= (e) => n.value.tr_driver_rto_address = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Driver Valid Up To" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_driver_valid_up_to,
								"onUpdate:modelValue": t[43] ||= (e) => n.value.tr_driver_valid_up_to = e,
								type: "date"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Driver Bank Account No" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_driver_bank_account_no,
								"onUpdate:modelValue": t[44] ||= (e) => n.value.tr_driver_bank_account_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, {
							label: "Driver Address",
							class: "md:col-span-2 xl:col-span-3"
						}, {
							default: C(() => [l(_, {
								modelValue: n.value.tr_driver_address,
								"onUpdate:modelValue": t[45] ||= (e) => n.value.tr_driver_address = e,
								rows: "2"
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				}),
				l(r, { class: "mb-4" }, {
					header: C(() => [...t[60] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Broker Details", -1)]]),
					default: C(() => [s("div", L, [
						l(p, { label: "Broker Name" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_broker_name,
								"onUpdate:modelValue": t[46] ||= (e) => n.value.tr_broker_name = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Broker Phone No" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_broker_phone,
								"onUpdate:modelValue": t[47] ||= (e) => n.value.tr_broker_phone = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Broker Pan No" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_broker_pan_no,
								"onUpdate:modelValue": t[48] ||= (e) => n.value.tr_broker_pan_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Advice Date" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_advice_date,
								"onUpdate:modelValue": t[49] ||= (e) => n.value.tr_advice_date = e,
								type: "date"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, { label: "Broker Bank Account No" }, {
							default: C(() => [l(c, {
								modelValue: n.value.tr_broker_bank_account_no,
								"onUpdate:modelValue": t[50] ||= (e) => n.value.tr_broker_bank_account_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(p, {
							label: "Broker Address",
							class: "md:col-span-2 xl:col-span-3"
						}, {
							default: C(() => [l(_, {
								modelValue: n.value.tr_broker_address,
								"onUpdate:modelValue": t[51] ||= (e) => n.value.tr_broker_address = e,
								rows: "2"
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				})
			])) : a("", !0);
		};
	}
}), z = {
	key: 0,
	class: "text-center py-8 text-muted"
}, B = {
	key: 1,
	class: "text-center py-8 text-muted"
}, V = {
	key: 2,
	class: "bg-surface border border-line-default rounded-xl overflow-hidden"
}, H = { class: "w-full text-sm" }, U = { class: "px-4 py-3 font-medium text-heading" }, W = { class: "px-4 py-3 text-body" }, He = { class: "px-4 py-3 text-body" }, G = { class: "px-4 py-3 text-body" }, Ue = { class: "px-4 py-3 text-body" }, K = { class: "px-4 py-3" }, q = { class: "px-2 py-1 text-xs rounded-full bg-primary-50 text-primary-600" }, J = /* @__PURE__ */ u({
	__name: "CustomerLorryReceiptsView",
	props: { client: { type: [Function, Object] } },
	setup(t) {
		let n = t, r = _([]), i = _(!0);
		m(async () => {
			try {
				let { data: e } = await n.client.get("/api/v1/customer/lorry-receipts");
				r.value = e.data || [];
			} catch {
				r.value = [];
			} finally {
				i.value = !1;
			}
		});
		function a(e) {
			return e ? new Date(e).toLocaleDateString() : "—";
		}
		return (t, n) => (h(), o("div", null, [n[1] ||= s("div", { class: "mb-6" }, [s("h1", { class: "text-2xl font-bold text-heading" }, "My Lorry Receipts"), s("p", { class: "text-sm text-muted mt-1" }, "Truck payment records where you are the owner, driver, or broker")], -1), i.value ? (h(), o("div", z, "Loading...")) : r.value.length === 0 ? (h(), o("div", B, "No lorry receipts found.")) : (h(), o("div", V, [s("table", H, [n[0] ||= s("thead", { class: "bg-surface-secondary border-b border-line-default" }, [s("tr", null, [
			s("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Receipt No"),
			s("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Truck No"),
			s("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Owner"),
			s("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Route"),
			s("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Date"),
			s("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Status")
		])], -1), s("tbody", null, [(h(!0), o(e, null, v(r.value, (e) => (h(), o("tr", {
			key: e.id,
			class: "border-b border-line-light"
		}, [
			s("td", U, x(e.invoice_number), 1),
			s("td", W, x(e.tr_lorry_no || "—"), 1),
			s("td", He, x(e.tr_owner_name || "—"), 1),
			s("td", G, x(e.tr_from_code || "—") + " → " + x(e.tr_to_code || "—"), 1),
			s("td", Ue, x(a(e.invoice_date)), 1),
			s("td", K, [s("span", q, x(e.status), 1)])
		]))), 128))])])]))]));
	}
});
//#endregion
//#region resources/js/init.ts
window.InvoiceShelf.booting((e, t, n) => {
	window.__lorryReceiptClient = n.client, window.__lorryReceiptCan = n.hasAbilities, n.addMessages({ en: { lorry_receipt: {
		title: "Lorry Receipts",
		challan_no: "Challan No.",
		party_name: "Party Name",
		owner_details: "Owner Details",
		driver_details: "Driver Details",
		broker_details: "Broker Details",
		section_c: "Section C: Advance Payment",
		section_e: "Section E: Final Settlement",
		contract_no: "Contract No",
		lorry_no: "Lorry No",
		paid_to: "Paid To",
		lorry_hire_amount: "Lorry Hire Amount",
		advance_amount: "Advance Amount",
		received_bilties: "Received Bilties (Docket Numbers)",
		net_amount_payable: "Net Amount Payable",
		party_profiles: "Party Profiles",
		my_receipts: "My Lorry Receipts"
	} } }), n.registerInvoiceFormSection({
		id: "lorry-receipt-fields",
		component: R
	}), n.registerInvoiceViewMode({
		id: "lorry-receipt-view-mode",
		value: "lorry_receipt",
		label: "Lorry Receipt",
		icon: "TruckIcon",
		createLink: "invoices/create?template=lorry_receipt",
		listLink: "/admin/invoices?view=lorry_receipt",
		ability: "lorry-receipt:view-lorry-receipt"
	}), n.registerInvoiceDocumentMeta({
		id: "lorry-receipt-doc-meta",
		templateName: "lorry_receipt",
		label: "Lorry Receipt",
		labelPlural: "Lorry Receipts",
		listLink: "/admin/invoices?view=lorry_receipt"
	}), n.registerPage({
		id: "customer-lorry-receipts",
		module: "lorry-receipt",
		path: "customer",
		component: J,
		meta: { title: "My Lorry Receipts" }
	});
});
//#endregion
