//#region resources/js/init.ts
var e = "http://www.w3.org/2000/svg", t = "InvoiceShelf", n = "HisabKitab", r = document.createElement("style");
r.textContent = `svg[aria-label="${t}"] > path{opacity:0!important}`, document.head.appendChild(r);
var i = !1;
function a() {
	i = !1, document.querySelectorAll(`svg[aria-label="${t}"]:not([data-rebranded])`).forEach((t) => {
		t.querySelectorAll(":scope > path").forEach((e) => e.remove());
		let r = document.createElementNS(e, "text");
		r.setAttribute("x", "60"), r.setAttribute("y", "33"), r.setAttribute("fill", "currentColor"), r.setAttribute("font-size", "24"), r.setAttribute("font-weight", "600"), r.setAttribute("font-family", "Poppins, Geologica, sans-serif"), r.textContent = n, t.appendChild(r), t.setAttribute("aria-label", n), t.setAttribute("data-rebranded", "");
	}), document.querySelectorAll(`img[alt="${t}"]`).forEach((e) => e.setAttribute("alt", n)), document.querySelectorAll(`img[alt="${t} Logo"]`).forEach((e) => e.setAttribute("alt", `${n} Logo`));
	let r = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, { acceptNode(e) {
		return e.textContent?.includes(t) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
	} }), a = [], o;
	for (; o = r.nextNode();) a.push(o);
	a.forEach((e) => {
		e.textContent &&= e.textContent.replaceAll(t, n);
	});
}
function o() {
	i || (i = !0, requestAnimationFrame(a));
}
window.InvoiceShelf.booting((e, t, n) => {
	n.addMessages({ en: {
		client: {
			connect_description: "Enter the address of the HisabKitab server you want to use.",
			app_lock_reason: "Unlock HisabKitab",
			app_lock_locked_heading: "HisabKitab is locked"
		},
		mcp: {
			consent: { subtitle: "{client} is asking to use your HisabKitab account." },
			connected_apps: {
				description: "AI assistants you have connected to HisabKitab. Each works in one of your companies, with the access you gave it.",
				connect_description: "Add this server to an assistant that supports MCP. It opens HisabKitab in your browser, where you sign in and choose the company and what the assistant may do."
			},
			admin: {
				description: "Let AI assistants such as Claude or ChatGPT work with HisabKitab through the Model Context Protocol (MCP). Each user connects their own assistant and chooses the company and what the assistant may do.",
				insecure_description: "Assistants refuse servers without HTTPS, and sign-in tokens would travel unencrypted. Serve HisabKitab over HTTPS before allowing assistants."
			}
		},
		settings: {
			company_info: { section_description: "Information about your company that will be displayed on invoices, estimates and other documents created by HisabKitab." },
			exchange_rate: { description: "Please enter exchange rate of all the currencies mentioned below to help HisabKitab properly calculate the amounts in {currency}." },
			tax_types: { description: "You can add or Remove Taxes as you please. HisabKitab supports Taxes on Individual Items as well as on the invoice." },
			update_app: {
				description: "You can easily update HisabKitab by checking for a new update by clicking the button below",
				client_description: "Check the server this app is connected to for a new HisabKitab release and install it. The app itself is updated through your device's app store."
			},
			disk: {
				description: "By default, HisabKitab will use your local disk for saving backups, avatar and other image files. You can configure more than one disk drivers like DigitalOcean, S3 and Dropbox according to your preference.",
				confirm_delete: "Your existing files & folders in the specified disk will not be affected but your disk configuration will be deleted from HisabKitab"
			}
		},
		wizard: {
			install_language: { description: "Select language wizard to install HisabKitab" },
			verify_domain: { desc: "HisabKitab uses Session based authentication which requires domain verification for security purposes. Please enter the domain on which you will be accessing your web application." },
			req: { system_req_desc: "HisabKitab has a few server requirements. Make sure that your server has the required php version and all the extensions mentioned below." }
		},
		demo: { banner: "This is the HisabKitab demo. Everyone shares it, and changes are wiped at every reset." }
	} });
	let r = () => {
		new MutationObserver(o).observe(document.body, {
			childList: !0,
			subtree: !0,
			characterData: !0
		}), a();
	};
	document.body ? r() : document.addEventListener("DOMContentLoaded", r);
});
//#endregion
