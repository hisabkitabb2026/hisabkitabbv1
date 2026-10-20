const { Fragment: e, Teleport: t, Transition: n, computed: r, createBlock: i, createCommentVNode: a, createElementBlock: o, createElementVNode: s, createTextVNode: ee, createVNode: c, defineComponent: l, nextTick: u, normalizeStyle: d, onBeforeUnmount: f, onMounted: p, openBlock: m, reactive: h, ref: g, renderList: _, resolveComponent: v, shallowRef: y, toDisplayString: b, vModelText: x, watch: S, withCtx: C, withDirectives: w, withModifiers: T } = window.__invoiceshelf_vue;
//#region resources/js/components/LorryPartySelectPopup.vue?vue&type=script&setup=true&lang.ts
var E = ["aria-label"], D = { class: "flex items-start gap-3" }, O = {
	class: "flex items-center justify-center w-11 h-11 text-sm font-semibold rounded-xl shrink-0 bg-btn-primary text-on-primary",
	"aria-hidden": "true"
}, k = { class: "flex-1 min-w-0" }, te = { class: "text-xs font-medium text-muted" }, A = { class: "text-base font-semibold truncate text-heading" }, ne = {
	key: 0,
	class: "text-sm truncate text-muted"
}, re = { class: "flex items-center gap-1 -me-1 shrink-0" }, ie = ["aria-label"], ae = ["aria-label"], oe = { key: 1 }, se = ["aria-expanded"], ce = { class: "flex flex-col flex-1 min-w-0" }, le = { class: "text-base font-semibold text-heading" }, ue = ["aria-label"], de = { class: "relative" }, fe = { class: "flex flex-col overflow-y-auto border-t border-line-light max-h-80 overscroll-contain" }, j = ["onClick"], pe = {
	class: "flex items-center justify-center w-10 h-10 text-sm font-semibold rounded-xl shrink-0 bg-primary-50 text-primary-700",
	"aria-hidden": "true"
}, me = { class: "flex flex-col min-w-0" }, he = { class: "flex items-center gap-2" }, ge = { class: "text-sm font-medium truncate text-heading" }, _e = { class: "text-xs px-2 py-0.5 rounded-full bg-primary-100 text-primary-700 font-medium" }, ve = {
	key: 0,
	class: "text-sm truncate text-muted"
}, ye = ["title", "onClick"], be = {
	class: "flex items-center justify-center w-10 h-10 text-sm font-semibold rounded-xl shrink-0 bg-surface-secondary text-muted",
	"aria-hidden": "true"
}, xe = { class: "flex flex-col min-w-0" }, Se = { class: "flex items-center gap-2" }, Ce = { class: "text-sm font-medium truncate text-heading" }, we = {
	key: 0,
	class: "text-sm truncate text-muted"
}, Te = {
	key: 0,
	class: "px-4 py-8 text-sm text-center text-muted",
	role: "status"
}, Ee = {
	key: 1,
	class: "flex flex-col gap-1 px-4 py-8 text-sm text-center",
	role: "status"
}, De = {
	key: 0,
	class: "text-muted"
}, Oe = { class: "font-medium text-heading" }, ke = { class: "w-full max-w-md p-6 bg-surface rounded-xl" }, Ae = { class: "mb-4 text-lg font-semibold text-heading" }, je = { class: "space-y-3" }, Me = { class: "mt-3 text-xs text-muted" }, Ne = { class: "flex gap-2 mt-4" }, Pe = { class: "w-full max-w-md p-6 bg-surface rounded-xl max-h-[90vh] overflow-y-auto" }, Fe = { class: "mb-4 text-lg font-semibold text-heading" }, Ie = { class: "space-y-3" }, Le = { class: "flex gap-2 mt-4" }, M = /* @__PURE__ */ l({
	__name: "LorryPartySelectPopup",
	props: {
		client: { type: [Function, Object] },
		type: {},
		label: {},
		modelValue: {}
	},
	emits: ["select", "clear"],
	setup(l, { emit: v }) {
		let y = l, M = v, N = g(!1), P = g(null), F = g([]), I = g([]), L = g(!1), R = g(y.modelValue ?? null), z = g(!1), B = g({
			name: "",
			phone: "",
			email: "",
			address: "",
			bank_account_no: "",
			licence_no: "",
			pan_number: ""
		}), V = g(null), H = g(null), U = g(null), W = g(null), G = h({
			top: "0px",
			left: "0px",
			width: "auto"
		});
		function K(e) {
			return e.split(" ").map((e) => e.charAt(0)).slice(0, 2).join("").toUpperCase();
		}
		async function q() {
			L.value = !0;
			try {
				let [e, t] = await Promise.all([y.client.get("/api/v1/lorry-receipts/lorry-party-profiles", { params: {
					type: y.type,
					search: P.value,
					limit: "all"
				} }), y.client.get("/api/v1/suppliers", { params: { limit: 100 } }).catch(() => null)]);
				F.value = e.data.data || [], I.value = t?.data?.data || [];
			} catch {
				F.value = [], I.value = [];
			} finally {
				L.value = !1;
			}
		}
		let J = r(() => {
			if (!P.value) return [];
			let e = new Set(F.value.map((e) => e.supplier_id).filter(Boolean)), t = P.value.toLowerCase();
			return I.value.filter((n) => !e.has(n.id) && (n.name.toLowerCase().includes(t) || (n.phone || "").includes(t)));
		});
		async function Re(e) {
			try {
				let { data: t } = await y.client.post("/api/v1/lorry-receipts/lorry-party-profiles", {
					type: y.type,
					supplier_id: e.id,
					name: e.name,
					phone: e.phone ?? ""
				});
				t?.data && X(t.data);
			} catch (e) {
				console.error("Failed to create profile from supplier", e);
			}
		}
		let ze;
		function Be() {
			clearTimeout(ze), ze = setTimeout(() => q(), 400);
		}
		function Ve() {
			if (!V.value) return;
			let e = V.value.getBoundingClientRect();
			G.top = e.bottom + window.scrollY + 8 + "px", G.left = e.left + window.scrollX + "px", G.width = e.width + "px";
		}
		function He() {
			N.value = !0, q(), u(() => {
				Ve(), W.value?.querySelector("input")?.focus();
			});
		}
		async function Y() {
			let e = N.value;
			N.value = !1, e && (await u(), (U.value ?? V.value)?.focus());
		}
		function X(e) {
			R.value = e, N.value = !1, P.value = null, M("select", e);
		}
		function Ue() {
			R.value = null, M("clear");
		}
		function We() {
			N.value = !1, B.value = {
				name: "",
				phone: "",
				email: "",
				address: "",
				bank_account_no: "",
				licence_no: "",
				pan_number: ""
			}, z.value = !0;
		}
		async function Ge() {
			try {
				let e = {
					type: y.type,
					name: B.value.name,
					phone: B.value.phone,
					email: B.value.email || null,
					address: B.value.address,
					bank_account_no: B.value.bank_account_no
				};
				y.type === "DRIVER" && (e.licence_no = B.value.licence_no), (y.type === "OWNER" || y.type === "BROKER") && (e.pan_number = B.value.pan_number);
				let { data: t } = await y.client.post("/api/v1/lorry-receipts/lorry-party-profiles", e);
				z.value = !1, t?.data && X(t.data);
			} catch (e) {
				console.error("Failed to create profile", e);
			}
		}
		let Ke = r(() => {
			let e = window;
			return typeof e.__lorryReceiptCan == "function" ? !!e.__lorryReceiptCan("lorry-receipt:manage-party-profiles") : !0;
		}), Z = g(!1), Q = g({
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
		function qe() {
			let e = R.value;
			e && (Q.value = {
				name: e.name ?? "",
				phone: e.phone ?? "",
				address: e.address ?? "",
				bank_account_no: e.bank_account_no ?? "",
				pan_number: e.pan_number ?? "",
				licence_no: e.licence_no ?? "",
				licence_date: e.licence_date ?? "",
				rto_address: e.rto_address ?? "",
				valid_up_to: e.valid_up_to ?? ""
			}, Z.value = !0);
		}
		async function Je() {
			let e = R.value;
			if (!(!e || !e.id)) try {
				let { data: t } = await y.client.put(`/api/v1/lorry-receipts/lorry-party-profiles/${e.id}`, {
					type: y.type,
					...Q.value
				});
				Z.value = !1, t?.data && (q(), X(t.data));
			} catch (e) {
				console.error("Failed to update profile", e);
			}
		}
		function Ye(e) {
			if (!N.value) return;
			let t = e.target;
			H.value?.contains(t) || V.value?.contains(t) || Y();
		}
		function Xe(e) {
			e.key === "Escape" && N.value && Y();
		}
		function $() {
			N.value && Ve();
		}
		return p(() => {
			document.addEventListener("click", Ye), document.addEventListener("keydown", Xe), window.addEventListener("scroll", $, !0), window.addEventListener("resize", $);
		}), f(() => {
			document.removeEventListener("click", Ye), document.removeEventListener("keydown", Xe), window.removeEventListener("scroll", $, !0), window.removeEventListener("resize", $);
		}), S(() => y.modelValue, (e) => {
			R.value = e ?? null;
		}), (r, u) => (m(), o("div", null, [
			R.value ? (m(), o("div", {
				key: 0,
				ref_key: "card",
				ref: U,
				tabindex: "-1",
				"aria-label": l.label + ": " + R.value.name,
				class: "flex flex-col gap-4 p-4 border md:p-5 glass rounded-xl focus:outline-hidden focus-visible:ring-2 focus-visible:ring-focus"
			}, [s("div", D, [
				s("span", O, b(K(R.value.name)), 1),
				s("div", k, [
					s("p", te, b(l.label), 1),
					s("p", A, b(R.value.name), 1),
					R.value.phone || R.value.address ? (m(), o("p", ne, b(R.value.phone || R.value.address), 1)) : a("", !0)
				]),
				s("div", re, [R.value.id && Ke.value ? (m(), o("button", {
					key: 0,
					type: "button",
					class: "flex items-center justify-center w-10 h-10 transition-colors rounded-lg md:w-9 md:h-9 text-muted hover:bg-hover-strong hover:text-heading",
					"aria-label": "Edit " + l.label,
					onClick: qe
				}, [...u[22] ||= [s("svg", {
					class: "w-5 h-5",
					fill: "none",
					stroke: "currentColor",
					viewBox: "0 0 24 24"
				}, [s("path", {
					"stroke-linecap": "round",
					"stroke-linejoin": "round",
					"stroke-width": "2",
					d: "M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
				})], -1)]], 8, ie)) : a("", !0), s("button", {
					type: "button",
					class: "flex items-center justify-center w-10 h-10 transition-colors rounded-lg md:w-9 md:h-9 text-muted hover:bg-hover-strong hover:text-heading",
					"aria-label": "Change " + l.label,
					onClick: Ue
				}, [...u[23] ||= [s("svg", {
					class: "w-5 h-5",
					fill: "none",
					stroke: "currentColor",
					viewBox: "0 0 24 24"
				}, [s("path", {
					"stroke-linecap": "round",
					"stroke-linejoin": "round",
					"stroke-width": "2",
					d: "M6 18L18 6M6 6l12 12"
				})], -1)]], 8, ae)])
			])], 8, E)) : (m(), o("div", oe, [s("button", {
				ref_key: "trigger",
				ref: V,
				type: "button",
				"aria-expanded": N.value,
				"aria-haspopup": "dialog",
				class: "flex items-center w-full gap-4 p-4 text-start transition-colors border-2 border-dashed md:p-5 rounded-xl bg-surface/50 hover:border-primary-400 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-focus",
				onClick: u[0] ||= (e) => N.value ? Y() : He()
			}, [
				u[25] ||= s("span", {
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
				s("span", ce, [s("span", le, b(l.label), 1), u[24] ||= s("span", { class: "text-sm text-muted" }, "Click to select", -1)]),
				u[26] ||= s("svg", {
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
			], 8, se)])),
			(m(), i(t, { to: "body" }, [c(n, {
				"enter-active-class": "transition duration-150 ease-out",
				"enter-from-class": "translate-y-1 opacity-0",
				"leave-active-class": "transition duration-100 ease-in",
				"leave-to-class": "translate-y-1 opacity-0"
			}, {
				default: C(() => [N.value ? (m(), o("div", {
					key: 0,
					ref_key: "panel",
					ref: H,
					role: "dialog",
					"aria-label": l.label,
					style: d({
						position: "absolute",
						top: G.top,
						left: G.left,
						width: G.width,
						zIndex: 9999
					}),
					class: "overflow-hidden border glass-strong rounded-xl shadow-xl"
				}, [
					s("div", {
						ref_key: "searchField",
						ref: W,
						class: "p-3"
					}, [s("div", de, [u[27] ||= s("svg", {
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
						"onUpdate:modelValue": u[1] ||= (e) => P.value = e,
						type: "search",
						placeholder: "Search...",
						class: "w-full py-2 pl-9 pr-3 text-sm border border-line-default rounded-lg bg-surface text-heading placeholder:text-subtle focus:outline-none focus:ring-2 focus:ring-focus",
						onInput: Be
					}, null, 544), [[x, P.value]])])], 512),
					s("ul", fe, [
						(m(!0), o(e, null, _(F.value, (e) => (m(), o("li", { key: "p" + e.id }, [s("button", {
							type: "button",
							class: "flex items-center w-full gap-3 px-4 py-3 text-start transition-colors hover:bg-hover-strong focus:outline-hidden focus-visible:bg-hover-strong",
							onClick: (t) => X(e)
						}, [s("span", pe, b(K(e.name)), 1), s("span", me, [s("span", he, [s("span", ge, b(e.name), 1), s("span", _e, b(e.type), 1)]), e.phone || e.address ? (m(), o("span", ve, b(e.phone || e.address), 1)) : a("", !0)])], 8, j)]))), 128)),
						(m(!0), o(e, null, _(J.value, (e) => (m(), o("li", { key: "s" + e.id }, [s("button", {
							type: "button",
							class: "flex items-center w-full gap-3 px-4 py-3 text-start transition-colors hover:bg-hover-strong focus:outline-hidden focus-visible:bg-hover-strong",
							title: "Use this supplier as the " + l.label,
							onClick: (t) => Re(e)
						}, [s("span", be, b(K(e.name)), 1), s("span", xe, [s("span", Se, [s("span", Ce, b(e.name), 1), u[28] ||= s("span", { class: "text-xs px-2 py-0.5 rounded-full bg-surface-secondary text-muted font-medium" }, " Supplier ", -1)]), e.phone || e.email ? (m(), o("span", we, b(e.phone || e.email), 1)) : a("", !0)])], 8, ye)]))), 128)),
						L.value ? (m(), o("li", Te, " Loading... ")) : F.value.length === 0 && J.value.length === 0 ? (m(), o("li", Ee, [P.value ? (m(), o("span", De, "No matches found")) : (m(), o(e, { key: 1 }, [s("span", Oe, "No " + b(l.label) + " profiles yet", 1), u[29] ||= s("span", { class: "text-muted" }, "Add one to get started", -1)], 64))])) : a("", !0)
					]),
					Ke.value ? (m(), o("button", {
						key: 0,
						type: "button",
						class: "flex items-center justify-center w-full gap-2 text-sm font-medium transition-colors border-t h-12 border-line-light text-primary-600 hover:bg-primary-50/60",
						onClick: We
					}, [u[30] ||= s("svg", {
						class: "w-5 h-5",
						fill: "none",
						stroke: "currentColor",
						viewBox: "0 0 24 24"
					}, [s("path", {
						"stroke-linecap": "round",
						"stroke-linejoin": "round",
						"stroke-width": "2",
						d: "M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
					})], -1), ee(" Add New " + b(l.label), 1)])) : a("", !0)
				], 12, ue)) : a("", !0)]),
				_: 1
			})])),
			(m(), i(t, { to: "body" }, [z.value ? (m(), o("div", {
				key: 0,
				class: "fixed inset-0 z-[10000] flex items-center justify-center bg-black/50",
				onClick: u[10] ||= T((e) => z.value = !1, ["self"])
			}, [s("div", ke, [
				s("h3", Ae, "New " + b(l.label), 1),
				s("div", je, [
					w(s("input", {
						"onUpdate:modelValue": u[2] ||= (e) => B.value.name = e,
						placeholder: "Name *",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[x, B.value.name]]),
					w(s("input", {
						"onUpdate:modelValue": u[3] ||= (e) => B.value.phone = e,
						placeholder: "Phone",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[x, B.value.phone]]),
					w(s("input", {
						"onUpdate:modelValue": u[4] ||= (e) => B.value.email = e,
						type: "email",
						placeholder: "Email",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[x, B.value.email]]),
					w(s("textarea", {
						"onUpdate:modelValue": u[5] ||= (e) => B.value.address = e,
						placeholder: "Address",
						rows: "2",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[x, B.value.address]]),
					w(s("input", {
						"onUpdate:modelValue": u[6] ||= (e) => B.value.bank_account_no = e,
						placeholder: "Bank Account No",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[x, B.value.bank_account_no]]),
					l.type === "DRIVER" ? w((m(), o("input", {
						key: 0,
						"onUpdate:modelValue": u[7] ||= (e) => B.value.licence_no = e,
						placeholder: "Licence No",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512)), [[x, B.value.licence_no]]) : a("", !0),
					l.type === "OWNER" || l.type === "BROKER" ? w((m(), o("input", {
						key: 1,
						"onUpdate:modelValue": u[8] ||= (e) => B.value.pan_number = e,
						placeholder: "PAN No",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512)), [[x, B.value.pan_number]]) : a("", !0)
				]),
				s("p", Me, " Also saved as a Supplier, so bills and payments can be tracked against this " + b(l.label) + ". ", 1),
				s("div", Ne, [s("button", {
					type: "button",
					class: "flex-1 px-4 py-2 text-sm font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700",
					onClick: Ge
				}, "Save"), s("button", {
					type: "button",
					class: "px-4 py-2 text-sm border border-line-default rounded-lg text-heading",
					onClick: u[9] ||= (e) => z.value = !1
				}, "Cancel")])
			])])) : a("", !0)])),
			(m(), i(t, { to: "body" }, [Z.value ? (m(), o("div", {
				key: 0,
				class: "fixed inset-0 z-[10000] flex items-center justify-center bg-black/50",
				onClick: u[21] ||= T((e) => Z.value = !1, ["self"])
			}, [s("div", Pe, [
				s("h3", Fe, "Edit " + b(l.label), 1),
				s("div", Ie, [
					w(s("input", {
						"onUpdate:modelValue": u[11] ||= (e) => Q.value.name = e,
						placeholder: "Name *",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[x, Q.value.name]]),
					w(s("input", {
						"onUpdate:modelValue": u[12] ||= (e) => Q.value.phone = e,
						placeholder: "Phone",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[x, Q.value.phone]]),
					w(s("textarea", {
						"onUpdate:modelValue": u[13] ||= (e) => Q.value.address = e,
						placeholder: "Address",
						rows: "2",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[x, Q.value.address]]),
					w(s("input", {
						"onUpdate:modelValue": u[14] ||= (e) => Q.value.bank_account_no = e,
						placeholder: "Bank Account No",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[x, Q.value.bank_account_no]]),
					l.type === "OWNER" || l.type === "BROKER" ? w((m(), o("input", {
						key: 0,
						"onUpdate:modelValue": u[15] ||= (e) => Q.value.pan_number = e,
						placeholder: "PAN No",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512)), [[x, Q.value.pan_number]]) : a("", !0),
					l.type === "DRIVER" ? (m(), o(e, { key: 1 }, [
						w(s("input", {
							"onUpdate:modelValue": u[16] ||= (e) => Q.value.licence_no = e,
							placeholder: "Licence No",
							class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
						}, null, 512), [[x, Q.value.licence_no]]),
						w(s("input", {
							"onUpdate:modelValue": u[17] ||= (e) => Q.value.licence_date = e,
							type: "date",
							placeholder: "Licence Date",
							class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
						}, null, 512), [[x, Q.value.licence_date]]),
						w(s("input", {
							"onUpdate:modelValue": u[18] ||= (e) => Q.value.rto_address = e,
							placeholder: "RTO",
							class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
						}, null, 512), [[x, Q.value.rto_address]]),
						w(s("input", {
							"onUpdate:modelValue": u[19] ||= (e) => Q.value.valid_up_to = e,
							type: "date",
							placeholder: "Valid Up To",
							class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
						}, null, 512), [[x, Q.value.valid_up_to]])
					], 64)) : a("", !0)
				]),
				s("div", Le, [s("button", {
					type: "button",
					class: "flex-1 px-4 py-2 text-sm font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700",
					onClick: Je
				}, "Save"), s("button", {
					type: "button",
					class: "px-4 py-2 text-sm border border-line-default rounded-lg text-heading",
					onClick: u[20] ||= (e) => Z.value = !1
				}, "Cancel")])
			])])) : a("", !0)]))
		]));
	}
}), N = {
	key: 0,
	class: "space-y-6 mt-5"
}, P = { class: "grid gap-4 grid-cols-1 md:grid-cols-3" }, F = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, I = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, L = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, R = { id: "lorry-advance-bank-list" }, z = ["value"], B = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, V = { id: "lorry-final-bank-list" }, H = ["value"], U = { class: "relative flex items-center" }, W = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, G = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, K = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, q = /* @__PURE__ */ l({
	__name: "LorryReceiptFormFields",
	props: {
		templateName: {},
		store: {}
	},
	setup(t) {
		let n = t, i = r(() => n.store?.newInvoice), ee = r(() => n.templateName === "lorry_receipt"), l = y(null);
		function u() {
			if (l.value) return l.value;
			let e = window;
			if (e.__lorryReceiptClient) return l.value = e.__lorryReceiptClient, l.value;
			throw Error("API client not initialized for LorryReceipt module");
		}
		let d = ["UMB", "VAPI"], f = g([]);
		async function h() {
			try {
				let { data: e } = await u().get("/api/v1/payment-methods", { params: { limit: "all" } });
				f.value = e?.data ?? [];
			} catch {
				f.value = [];
			}
		}
		let b = g([]), x = [
			"State Bank of India (SBI)",
			"HDFC Bank",
			"ICICI Bank",
			"Punjab National Bank (PNB)",
			"Bank of Baroda (BOB)",
			"Axis Bank",
			"Kotak Mahindra Bank",
			"Canara Bank",
			"Union Bank of India",
			"Bank of India",
			"Central Bank of India",
			"Indian Bank",
			"IndusInd Bank",
			"Yes Bank",
			"IDBI Bank",
			"Federal Bank",
			"Cash / Self"
		], w = r(() => {
			let e = b.value.map((e) => e.name), t = [...x, ...e];
			return i.value?.tr_advance_bank && t.push(String(i.value.tr_advance_bank)), i.value?.tr_final_bank && t.push(String(i.value.tr_final_bank)), Array.from(new Set(t.map((e) => e.trim()).filter(Boolean)));
		});
		async function T() {
			try {
				let { data: e } = await u().get("/api/v1/lorry-receipts/banks");
				b.value = e?.data ?? [];
			} catch {
				b.value = [];
			}
		}
		let E = [
			["tr_owner_name", "name"],
			["tr_owner_address", "address"],
			["tr_owner_phone", "phone"],
			["tr_owner_bank_account_no", "bank_account_no"],
			["tr_owner_pan_no", "pan_number"]
		], D = [
			["tr_driver_name", "name"],
			["tr_driver_address", "address"],
			["tr_driver_licence_no", "licence_no"],
			["tr_driver_licence_date", "licence_date"],
			["tr_driver_rto_address", "rto_address"],
			["tr_driver_valid_up_to", "valid_up_to"],
			["tr_driver_bank_account_no", "bank_account_no"]
		], O = [
			["tr_broker_name", "name"],
			["tr_broker_address", "address"],
			["tr_broker_pan_no", "pan_number"],
			["tr_advice_date", "advice_date"],
			["tr_broker_phone", "phone"],
			["tr_broker_bank_account_no", "bank_account_no"]
		];
		function k(e, t) {
			i.value && e.forEach(([e, n]) => {
				i.value[e] = t?.[n] || "";
			});
		}
		function te(e) {
			if (!i.value) return null;
			let t = i.value, n = {};
			for (let [r, i] of e) n[i] = t[r] ?? "";
			return n.name ? {
				id: 0,
				type: "",
				...n
			} : null;
		}
		function A(e, t) {
			let n = te(e);
			if (!n) return null;
			let r = Number(t) || 0;
			return r ? {
				...n,
				id: r
			} : n;
		}
		let ne = r(() => A(E, i.value?.tr_owner_profile_id)), re = r(() => A(D, i.value?.tr_driver_profile_id)), ie = r(() => A(O, i.value?.tr_broker_profile_id));
		function ae(e) {
			i.value && (i.value.tr_owner_profile_id = e.id), k(E, e);
		}
		function oe() {
			i.value && (i.value.tr_owner_profile_id = null), k(E, null);
		}
		function se(e) {
			i.value && (i.value.tr_driver_profile_id = e.id), k(D, e);
		}
		function ce() {
			i.value && (i.value.tr_driver_profile_id = null), k(D, null);
		}
		function le(e) {
			i.value && (i.value.tr_broker_profile_id = e.id), k(O, e);
		}
		function ue() {
			i.value && (i.value.tr_broker_profile_id = null), k(O, null);
		}
		function de(e) {
			let t = i.value?.items;
			if (!t || !Array.isArray(t)) return;
			let n = Math.round(e * 100);
			if (t.length > 0) {
				let e = t[0];
				e.name = e.name || "Lorry Hire", e.quantity = 1, e.price = n, e.total = n;
				return;
			}
			t.push({
				id: `tr-lorry-hire-${Date.now()}`,
				invoice_id: null,
				item_id: null,
				name: "Lorry Hire",
				description: "Lorry Hire Charges",
				quantity: 1,
				price: n,
				discount_type: "fixed",
				discount_val: 0,
				discount: 0,
				total: n,
				totalTax: 0,
				taxes: []
			});
		}
		function fe() {
			if (!i.value) return;
			let e = Number(i.value.tr_lorry_hire_amount) || 0, t = Number(i.value.tr_other_charges_amount) || 0, n = Number(i.value.tr_advance_amount) || 0, r = Number(i.value.tr_detention_amount) || 0, a = Number(i.value.tr_extra_hire_amount) || 0, o = Number(i.value.tr_final_other_amount) || 0, s = Number(i.value.tr_less_advance_other_branch_amount) || 0, ee = Number(i.value.tr_less_deduction_claims_amount) || 0, c = e + t, l = Math.max(0, c - n);
			i.value.tr_balance_amount = String(l);
			let u = c + r + a + o - n - s - ee;
			i.value.tr_net_amount_payable = String(u);
			let d = Math.round(c * 100);
			i.value.sub_total = d, i.value.total = d, de(c);
		}
		S(() => [
			i.value?.tr_lorry_hire_amount,
			i.value?.tr_other_charges_amount,
			i.value?.tr_advance_amount,
			i.value?.tr_detention_amount,
			i.value?.tr_extra_hire_amount,
			i.value?.tr_final_other_amount,
			i.value?.tr_less_advance_other_branch_amount,
			i.value?.tr_less_deduction_claims_amount,
			i.value?.tr_final_amount_paid
		], () => fe());
		let j = null;
		return S(() => i.value?.customer, (e) => {
			if (!e?.name || !i.value) return;
			let t = e.id ?? null;
			(!i.value.tr_paid_to || j !== null && t !== j) && (i.value.tr_paid_to = e.name, i.value.tr_final_paid_to = e.name), j = t;
		}, { immediate: !0 }), p(() => {
			h(), T();
		}), (t, n) => {
			let r = v("BaseCard"), l = v("BaseInput"), p = v("BaseInputGroup"), h = v("BaseSelectInput"), g = v("BaseTextarea");
			return ee.value && i.value ? (m(), o("div", N, [
				c(r, { class: "mb-4" }, {
					header: C(() => [...n[54] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Select Party (Owner / Driver / Broker)", -1)]]),
					default: C(() => [n[55] ||= s("p", { class: "text-sm text-muted mb-4" }, "Select a party profile to auto-fill the Owner, Driver, and Broker details below.", -1), s("div", P, [
						c(M, {
							client: u(),
							type: "OWNER",
							label: "Owner",
							"model-value": ne.value,
							onSelect: ae,
							onClear: oe
						}, null, 8, ["client", "model-value"]),
						c(M, {
							client: u(),
							type: "DRIVER",
							label: "Driver",
							"model-value": re.value,
							onSelect: se,
							onClear: ce
						}, null, 8, ["client", "model-value"]),
						c(M, {
							client: u(),
							type: "BROKER",
							label: "Broker",
							"model-value": ie.value,
							onSelect: le,
							onClear: ue
						}, null, 8, ["client", "model-value"])
					])]),
					_: 1
				}),
				c(r, { class: "mb-4" }, {
					header: C(() => [...n[56] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Trip Details", -1)]]),
					default: C(() => [s("div", F, [
						c(p, { label: "From" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_from_name,
								"onUpdate:modelValue": n[0] ||= (e) => i.value.tr_from_name = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "To" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_to_name,
								"onUpdate:modelValue": n[1] ||= (e) => i.value.tr_to_name = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "LR Number" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_received_no_bilties,
								"onUpdate:modelValue": n[2] ||= (e) => i.value.tr_received_no_bilties = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "No Of Packages" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_no_of_packages,
								"onUpdate:modelValue": n[3] ||= (e) => i.value.tr_no_of_packages = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Actual Weight" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_actual_weight,
								"onUpdate:modelValue": n[4] ||= (e) => i.value.tr_actual_weight = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Charge Weight" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_charged_weight,
								"onUpdate:modelValue": n[5] ||= (e) => i.value.tr_charged_weight = e
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				}),
				c(r, { class: "mb-4" }, {
					header: C(() => [...n[57] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Vehicle Details", -1)]]),
					default: C(() => [s("div", I, [
						c(p, { label: "Lorry No" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_lorry_no,
								"onUpdate:modelValue": n[6] ||= (e) => i.value.tr_lorry_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Regd at" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_regd_at,
								"onUpdate:modelValue": n[7] ||= (e) => i.value.tr_regd_at = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Body Type" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_body_type,
								"onUpdate:modelValue": n[8] ||= (e) => i.value.tr_body_type = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Make" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_make,
								"onUpdate:modelValue": n[9] ||= (e) => i.value.tr_make = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Model" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_vehicle_model,
								"onUpdate:modelValue": n[10] ||= (e) => i.value.tr_vehicle_model = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Colour" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_colour,
								"onUpdate:modelValue": n[11] ||= (e) => i.value.tr_colour = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Chasis No" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_chasis_no,
								"onUpdate:modelValue": n[12] ||= (e) => i.value.tr_chasis_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Engine No" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_engine_no,
								"onUpdate:modelValue": n[13] ||= (e) => i.value.tr_engine_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				}),
				c(r, { class: "mb-4" }, {
					header: C(() => [...n[58] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Hire Particulars", -1)]]),
					default: C(() => [s("div", L, [
						c(p, { label: "Paid To" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_paid_to,
								"onUpdate:modelValue": n[14] ||= (e) => i.value.tr_paid_to = e,
								placeholder: "Auto-filled from Customer selection"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Lorry Hire" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_lorry_hire_amount,
								"onUpdate:modelValue": n[15] ||= (e) => i.value.tr_lorry_hire_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Add Other Charges" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_other_charges_amount,
								"onUpdate:modelValue": n[16] ||= (e) => i.value.tr_other_charges_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Advance Payment Mode" }, {
							default: C(() => [c(h, {
								modelValue: i.value.tr_advance_payment_method_id,
								"onUpdate:modelValue": n[17] ||= (e) => i.value.tr_advance_payment_method_id = e,
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
						c(p, { label: "Advance On" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_advance_on,
								"onUpdate:modelValue": n[18] ||= (e) => i.value.tr_advance_on = e,
								type: "date"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Bank" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_advance_bank,
								"onUpdate:modelValue": n[19] ||= (e) => i.value.tr_advance_bank = e,
								list: "lorry-advance-bank-list",
								placeholder: "Type or select Bank"
							}, null, 8, ["modelValue"]), s("datalist", R, [(m(!0), o(e, null, _(w.value, (e) => (m(), o("option", {
								key: e,
								value: e
							}, null, 8, z))), 128))])]),
							_: 1
						}),
						c(p, { label: "Advance Paid Rs" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_advance_amount,
								"onUpdate:modelValue": n[20] ||= (e) => i.value.tr_advance_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Balance / Remaining Amount Rs" }, {
							default: C(() => [c(l, {
								"model-value": i.value.tr_balance_amount,
								readonly: "",
								placeholder: "Auto-calculated (Gross Hire - Advance)"
							}, null, 8, ["model-value"])]),
							_: 1
						}),
						c(p, { label: "Balance Payable at" }, {
							default: C(() => [c(h, {
								modelValue: i.value.tr_balance_payable_at,
								"onUpdate:modelValue": n[21] ||= (e) => i.value.tr_balance_payable_at = e,
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
						c(p, { label: "Loaded By" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_loaded_by,
								"onUpdate:modelValue": n[22] ||= (e) => i.value.tr_loaded_by = e
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				}),
				c(r, { class: "mb-4" }, {
					header: C(() => [...n[59] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Final Payment Details", -1)]]),
					default: C(() => [s("div", B, [
						c(p, { label: "Final Paid To" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_final_paid_to,
								"onUpdate:modelValue": n[23] ||= (e) => i.value.tr_final_paid_to = e,
								placeholder: "Auto-filled from Customer selection"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Add Detention Rs." }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_detention_amount,
								"onUpdate:modelValue": n[24] ||= (e) => i.value.tr_detention_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Extra Hire Rs" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_extra_hire_amount,
								"onUpdate:modelValue": n[25] ||= (e) => i.value.tr_extra_hire_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Other Rs" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_final_other_amount,
								"onUpdate:modelValue": n[26] ||= (e) => i.value.tr_final_other_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Less Adv. at other branch" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_less_advance_other_branch_amount,
								"onUpdate:modelValue": n[27] ||= (e) => i.value.tr_less_advance_other_branch_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Less Deduction for Claims" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_less_deduction_claims_amount,
								"onUpdate:modelValue": n[28] ||= (e) => i.value.tr_less_deduction_claims_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Final Balance Amount Paid at" }, {
							default: C(() => [c(h, {
								modelValue: i.value.tr_final_balance_paid_at,
								"onUpdate:modelValue": n[29] ||= (e) => i.value.tr_final_balance_paid_at = e,
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
						c(p, { label: "Final Balance Date" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_final_balance_on,
								"onUpdate:modelValue": n[30] ||= (e) => i.value.tr_final_balance_on = e,
								type: "date"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Final Payment Mode" }, {
							default: C(() => [c(h, {
								modelValue: i.value.tr_final_payment_method_id,
								"onUpdate:modelValue": n[31] ||= (e) => i.value.tr_final_payment_method_id = e,
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
						c(p, { label: "Final Bank" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_final_bank,
								"onUpdate:modelValue": n[32] ||= (e) => i.value.tr_final_bank = e,
								list: "lorry-final-bank-list",
								placeholder: "Type or select Bank"
							}, null, 8, ["modelValue"]), s("datalist", V, [(m(!0), o(e, null, _(w.value, (e) => (m(), o("option", {
								key: e,
								value: e
							}, null, 8, H))), 128))])]),
							_: 1
						}),
						c(p, { label: "Net Amount Payable Rs" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_net_amount_payable,
								"onUpdate:modelValue": n[33] ||= (e) => i.value.tr_net_amount_payable = e,
								type: "number",
								readonly: ""
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Final / Remaining Amount Paid Rs" }, {
							default: C(() => [s("div", U, [c(l, {
								modelValue: i.value.tr_final_amount_paid,
								"onUpdate:modelValue": n[34] ||= (e) => i.value.tr_final_amount_paid = e,
								type: "number",
								placeholder: "Enter amount paid"
							}, null, 8, ["modelValue"]), i.value.tr_net_amount_payable ? (m(), o("button", {
								key: 0,
								type: "button",
								class: "absolute end-2 text-xs text-primary-600 hover:text-primary-800 font-medium px-2 py-1 bg-surface rounded border border-line-light cursor-pointer shadow-xs",
								title: "Copy Net Amount Payable",
								onClick: n[35] ||= (e) => i.value.tr_final_amount_paid = i.value.tr_net_amount_payable
							}, " Fill Net ")) : a("", !0)])]),
							_: 1
						})
					])]),
					_: 1
				}),
				c(r, { class: "mb-4" }, {
					header: C(() => [...n[60] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Owner Details", -1)]]),
					default: C(() => [s("div", W, [
						c(p, { label: "Owner Name" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_owner_name,
								"onUpdate:modelValue": n[36] ||= (e) => i.value.tr_owner_name = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Owner Phone No" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_owner_phone,
								"onUpdate:modelValue": n[37] ||= (e) => i.value.tr_owner_phone = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Owner Bank Account No" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_owner_bank_account_no,
								"onUpdate:modelValue": n[38] ||= (e) => i.value.tr_owner_bank_account_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Owner PAN No" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_owner_pan_no,
								"onUpdate:modelValue": n[39] ||= (e) => i.value.tr_owner_pan_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, {
							label: "Owner Address",
							class: "md:col-span-2 xl:col-span-3"
						}, {
							default: C(() => [c(g, {
								modelValue: i.value.tr_owner_address,
								"onUpdate:modelValue": n[40] ||= (e) => i.value.tr_owner_address = e,
								rows: "2"
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				}),
				c(r, { class: "mb-4" }, {
					header: C(() => [...n[61] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Driver Details", -1)]]),
					default: C(() => [s("div", G, [
						c(p, { label: "Driver Name" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_driver_name,
								"onUpdate:modelValue": n[41] ||= (e) => i.value.tr_driver_name = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Driver Licence No" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_driver_licence_no,
								"onUpdate:modelValue": n[42] ||= (e) => i.value.tr_driver_licence_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Driver Licence Date" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_driver_licence_date,
								"onUpdate:modelValue": n[43] ||= (e) => i.value.tr_driver_licence_date = e,
								type: "date"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Driver RTO" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_driver_rto_address,
								"onUpdate:modelValue": n[44] ||= (e) => i.value.tr_driver_rto_address = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Driver Valid Up To" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_driver_valid_up_to,
								"onUpdate:modelValue": n[45] ||= (e) => i.value.tr_driver_valid_up_to = e,
								type: "date"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Driver Bank Account No" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_driver_bank_account_no,
								"onUpdate:modelValue": n[46] ||= (e) => i.value.tr_driver_bank_account_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, {
							label: "Driver Address",
							class: "md:col-span-2 xl:col-span-3"
						}, {
							default: C(() => [c(g, {
								modelValue: i.value.tr_driver_address,
								"onUpdate:modelValue": n[47] ||= (e) => i.value.tr_driver_address = e,
								rows: "2"
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				}),
				c(r, { class: "mb-4" }, {
					header: C(() => [...n[62] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Broker Details", -1)]]),
					default: C(() => [s("div", K, [
						c(p, { label: "Broker Name" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_broker_name,
								"onUpdate:modelValue": n[48] ||= (e) => i.value.tr_broker_name = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Broker Phone No" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_broker_phone,
								"onUpdate:modelValue": n[49] ||= (e) => i.value.tr_broker_phone = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Broker Pan No" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_broker_pan_no,
								"onUpdate:modelValue": n[50] ||= (e) => i.value.tr_broker_pan_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Advice Date" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_advice_date,
								"onUpdate:modelValue": n[51] ||= (e) => i.value.tr_advice_date = e,
								type: "date"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, { label: "Broker Bank Account No" }, {
							default: C(() => [c(l, {
								modelValue: i.value.tr_broker_bank_account_no,
								"onUpdate:modelValue": n[52] ||= (e) => i.value.tr_broker_bank_account_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(p, {
							label: "Broker Address",
							class: "md:col-span-2 xl:col-span-3"
						}, {
							default: C(() => [c(g, {
								modelValue: i.value.tr_broker_address,
								"onUpdate:modelValue": n[53] ||= (e) => i.value.tr_broker_address = e,
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
}), J = {
	key: 0,
	class: "text-center py-8 text-muted"
}, Re = {
	key: 1,
	class: "text-center py-8 text-muted"
}, ze = {
	key: 2,
	class: "bg-surface border border-line-default rounded-xl overflow-hidden"
}, Be = { class: "w-full text-sm" }, Ve = { class: "px-4 py-3 font-medium text-heading" }, He = { class: "px-4 py-3 text-body" }, Y = { class: "px-4 py-3 text-body" }, X = { class: "px-4 py-3 text-body" }, Ue = { class: "px-4 py-3 text-body" }, We = { class: "px-4 py-3" }, Ge = { class: "px-2 py-1 text-xs rounded-full bg-primary-50 text-primary-600" }, Ke = /* @__PURE__ */ l({
	__name: "CustomerLorryReceiptsView",
	props: { client: { type: [Function, Object] } },
	setup(t) {
		let n = t, r = g([]), i = g(!0);
		p(async () => {
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
		return (t, n) => (m(), o("div", null, [n[1] ||= s("div", { class: "mb-6" }, [s("h1", { class: "text-2xl font-bold text-heading" }, "My Lorry Receipts"), s("p", { class: "text-sm text-muted mt-1" }, "Truck payment records where you are the owner, driver, or broker")], -1), i.value ? (m(), o("div", J, "Loading...")) : r.value.length === 0 ? (m(), o("div", Re, "No lorry receipts found.")) : (m(), o("div", ze, [s("table", Be, [n[0] ||= s("thead", { class: "bg-surface-secondary border-b border-line-default" }, [s("tr", null, [
			s("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Receipt No"),
			s("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Truck No"),
			s("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Owner"),
			s("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Route"),
			s("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Date"),
			s("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Status")
		])], -1), s("tbody", null, [(m(!0), o(e, null, _(r.value, (e) => (m(), o("tr", {
			key: e.id,
			class: "border-b border-line-light"
		}, [
			s("td", Ve, b(e.invoice_number), 1),
			s("td", He, b(e.tr_lorry_no || "—"), 1),
			s("td", Y, b(e.tr_owner_name || "—"), 1),
			s("td", X, b(e.tr_from_name || "—") + " → " + b(e.tr_to_name || "—"), 1),
			s("td", Ue, b(a(e.invoice_date)), 1),
			s("td", We, [s("span", Ge, b(e.status), 1)])
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
		component: q
	}), n.registerInvoiceViewMode({
		id: "lorry-receipt-view-mode",
		value: "lorry_receipt",
		label: "Lorry Receipt",
		icon: "TruckIcon",
		createLink: "invoices/create?template=lorry_receipt",
		listLink: "/admin/invoices?view=lorry_receipt",
		ability: "lorry-receipt:view-lorry-receipt",
		columns: [{
			key: "tr_paid_to",
			label: "Paid To"
		}]
	}), n.registerInvoiceDocumentMeta({
		id: "lorry-receipt-doc-meta",
		templateName: "lorry_receipt",
		label: "Lorry Receipt",
		labelPlural: "Lorry Receipts",
		listLink: "/admin/invoices?view=lorry_receipt",
		dateLabel: "Challan Date",
		numberLabel: "Challan No."
	}), n.registerPage({
		id: "customer-lorry-receipts",
		module: "lorry-receipt",
		path: "customer",
		component: Ke,
		meta: { title: "My Lorry Receipts" }
	});
});
//#endregion
