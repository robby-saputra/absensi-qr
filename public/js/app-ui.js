document.addEventListener("DOMContentLoaded", () => {
    const sendHeartbeat = () => {
        if (window.location.pathname === "/login") {
            return;
        }

        fetch("/heartbeat", {
            headers: { Accept: "application/json" },
            cache: "no-store",
        }).catch(() => {});
    };

    sendHeartbeat();
    window.setInterval(sendHeartbeat, 30000);

    const addIcon = (element, icon) => {
        if (element.querySelector("i")) {
            return;
        }

        const item = document.createElement("i");
        item.className = `fa-solid ${icon}`;
        item.setAttribute("aria-hidden", "true");
        element.prepend(item);
    };

    document
        .querySelectorAll(
            ".btn, .btn-edit, .btn-hapus, .btn-kembali, .btn-tambah, .btn-reset, .back, .edit, .hapus, button, a.danger",
        )
        .forEach((element) => {
            const href = element.getAttribute("href") || "";
            const text = element.textContent.trim().toLowerCase();

            if (
                element.classList.contains("toggle-btn") ||
                element.classList.contains("link") ||
                element.classList.contains("sidebar-parent")
            ) {
                return;
            }

            const isDangerAction =
                element.classList.contains("danger") &&
                ["A", "BUTTON"].includes(element.tagName);

            if (
                href.includes("/delete") ||
                element.classList.contains("hapus") ||
                isDangerAction ||
                text.includes("hapus")
            ) {
                addIcon(element, "fa-trash");
            } else if (
                href.includes("/edit") ||
                element.classList.contains("edit") ||
                text.includes("edit")
            ) {
                addIcon(element, "fa-pen-to-square");
            } else if (href.includes("/create") || text.includes("tambah")) {
                addIcon(element, "fa-plus");
            } else if (
                href.includes("reset-password") ||
                text.includes("reset")
            ) {
                addIcon(element, "fa-rotate-left");
            } else if (href.includes("import") || text.includes("import")) {
                addIcon(element, "fa-file-import");
            } else if (
                href.includes("template") ||
                href.includes("export") ||
                text.includes("export")
            ) {
                addIcon(element, "fa-file-arrow-down");
            } else if (
                text.includes("kembali") ||
                href === "/dashboard/admin"
            ) {
                addIcon(element, "fa-arrow-left");
            } else if (text.includes("tampilkan") || text.includes("cari")) {
                addIcon(element, "fa-magnifying-glass");
            } else if (text.includes("nonaktif")) {
                addIcon(element, "fa-user-slash");
            } else if (text.includes("aktif")) {
                addIcon(element, "fa-user-check");
            } else if (element.tagName === "BUTTON") {
                addIcon(element, "fa-check");
            }
        });

    const isAdminPage = window.location.pathname.startsWith("/dashboard/admin");

    const ensureAdminFeedbackRoot = () => {
        let root = document.getElementById("admin-feedback-root");
        if (root) {
            return root;
        }

        root = document.createElement("div");
        root.id = "admin-feedback-root";
        root.innerHTML = `
            <div class="admin-toast-stack" aria-live="polite" aria-atomic="true"></div>
            <div class="admin-modal-backdrop" hidden>
                <div class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="admin-modal-title">
                    <div class="admin-modal-icon"><i class="fa-solid fa-circle-question" aria-hidden="true"></i></div>
                    <div class="admin-modal-body">
                        <h2 id="admin-modal-title">Konfirmasi Aksi</h2>
                        <p id="admin-modal-message">Lanjutkan aksi ini?</p>
                    </div>
                    <div class="admin-modal-actions">
                        <button type="button" class="admin-ui-btn secondary" data-admin-modal-cancel>Batal</button>
                        <button type="button" class="admin-ui-btn primary" data-admin-modal-confirm>Ya, lanjutkan</button>
                    </div>
                </div>
            </div>
            <div class="admin-loading-backdrop" hidden>
                <div class="admin-loading-panel">
                    <span class="admin-spinner" aria-hidden="true"></span>
                    <strong>Memproses data</strong>
                    <p>Mohon tunggu sebentar.</p>
                </div>
            </div>
        `;
        document.body.appendChild(root);

        return root;
    };

    const showAdminToast = ({
        type = "success",
        title = "Berhasil",
        message = "",
        timeout = 3600,
    }) => {
        if (!isAdminPage) {
            return;
        }

        const root = ensureAdminFeedbackRoot();
        const stack = root.querySelector(".admin-toast-stack");
        const toast = document.createElement("div");
        toast.className = `admin-toast ${type}`;
        toast.innerHTML = `
            <i class="fa-solid ${type === "success" ? "fa-circle-check" : "fa-triangle-exclamation"}" aria-hidden="true"></i>
            <div>
                <strong></strong>
                <p></p>
            </div>
            <button type="button" aria-label="Tutup notifikasi"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
        `;
        toast.querySelector("strong").textContent = title;
        toast.querySelector("p").textContent = message;

        const close = () => {
            toast.classList.add("is-hiding");
            window.setTimeout(() => toast.remove(), 220);
        };

        toast.querySelector("button")?.addEventListener("click", close);
        stack.appendChild(toast);
        window.setTimeout(close, timeout);
    };

    const showAdminConfirm = ({
        title = "Konfirmasi Aksi",
        message = "Lanjutkan aksi ini?",
        confirmText = "Ya, lanjutkan",
        danger = false,
    } = {}) => {
        if (!isAdminPage) {
            return Promise.resolve(confirm(message));
        }

        const root = ensureAdminFeedbackRoot();
        const backdrop = root.querySelector(".admin-modal-backdrop");
        const modal = root.querySelector(".admin-modal");
        const icon = root.querySelector(".admin-modal-icon i");
        const titleEl = root.querySelector("#admin-modal-title");
        const messageEl = root.querySelector("#admin-modal-message");
        const confirmButton = root.querySelector("[data-admin-modal-confirm]");
        const cancelButton = root.querySelector("[data-admin-modal-cancel]");

        titleEl.textContent = title;
        messageEl.textContent = message;
        confirmButton.textContent = confirmText;
        modal.classList.toggle("danger", danger);
        icon.className = `fa-solid ${danger ? "fa-triangle-exclamation" : "fa-circle-question"}`;
        backdrop.hidden = false;
        window.setTimeout(() => modal.classList.add("is-open"), 10);
        confirmButton.focus();

        return new Promise((resolve) => {
            const cleanup = (answer) => {
                modal.classList.remove("is-open");
                window.setTimeout(() => {
                    backdrop.hidden = true;
                    resolve(answer);
                }, 180);
                confirmButton.removeEventListener("click", onConfirm);
                cancelButton.removeEventListener("click", onCancel);
                backdrop.removeEventListener("click", onBackdrop);
                document.removeEventListener("keydown", onKeydown);
            };
            const onConfirm = () => cleanup(true);
            const onCancel = () => cleanup(false);
            const onBackdrop = (event) => {
                if (event.target === backdrop) {
                    cleanup(false);
                }
            };
            const onKeydown = (event) => {
                if (event.key === "Escape") {
                    cleanup(false);
                }
            };

            confirmButton.addEventListener("click", onConfirm);
            cancelButton.addEventListener("click", onCancel);
            backdrop.addEventListener("click", onBackdrop);
            document.addEventListener("keydown", onKeydown);
        });
    };

    const showAdminLoading = (message = "Mohon tunggu sebentar.") => {
        if (!isAdminPage) {
            return;
        }

        const root = ensureAdminFeedbackRoot();
        const backdrop = root.querySelector(".admin-loading-backdrop");
        const panel = root.querySelector(".admin-loading-panel");
        root.querySelector(".admin-loading-panel p").textContent = message;
        backdrop.hidden = false;
        window.setTimeout(() => panel.classList.add("is-open"), 10);
    };

    const bulkDeleteResourceFromHref = (href) => {
        const match = href.match(/\/dashboard\/admin\/([^/]+)\/delete\/(\d+)/);
        if (!match) {
            return null;
        }

        return {
            resource: match[1],
            id: match[2],
        };
    };

    const enhanceBulkDeleteTables = () => {
        if (!isAdminPage) {
            return;
        }

        document.querySelectorAll("table").forEach((table, tableIndex) => {
            const deleteLinks = Array.from(
                table.querySelectorAll(
                    'a[href*="/dashboard/admin/"][href*="/delete/"]',
                ),
            )
                .map((link) => ({
                    link,
                    parsed: bulkDeleteResourceFromHref(
                        link.getAttribute("href") || "",
                    ),
                }))
                .filter((item) => item.parsed);

            if (!deleteLinks.length) {
                return;
            }

            const resource = deleteLinks[0].parsed.resource;
            if (
                !deleteLinks.every((item) => item.parsed.resource === resource)
            ) {
                return;
            }

            const headRow =
                table.querySelector("thead tr") || table.querySelector("tr");
            if (!headRow || headRow.querySelector(".bulk-delete-select-all")) {
                return;
            }

            const headCell = document.createElement(
                headRow.children[0]?.tagName === "TD" ? "td" : "th",
            );
            headCell.className = "bulk-delete-cell";
            headCell.innerHTML =
                '<input type="checkbox" class="bulk-delete-select-all" aria-label="Pilih semua data">';
            headRow.insertBefore(headCell, headRow.firstChild);

            const selectedIds = [];
            deleteLinks.forEach(({ link, parsed }) => {
                const row = link.closest("tr");
                if (!row || row.querySelector(".bulk-delete-row-check")) {
                    return;
                }

                const cell = document.createElement("td");
                cell.className = "bulk-delete-cell";
                cell.innerHTML = `<input type="checkbox" class="bulk-delete-row-check" name="bulk_delete_ids[]" value="${parsed.id}" aria-label="Pilih data">`;
                row.insertBefore(cell, row.firstChild);
                selectedIds.push(parsed.id);
            });

            if (!selectedIds.length) {
                headCell.remove();
                return;
            }

            const wrapper = document.createElement("div");
            wrapper.className = "bulk-delete-toolbar";
            wrapper.innerHTML = `
                <button type="button" class="btn bulk-delete-button" disabled>
                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                    Hapus Terpilih
                </button>
                <span class="bulk-delete-count">0 data dipilih</span>
            `;
            table.parentNode.insertBefore(wrapper, table);

            const selectAll = headCell.querySelector(".bulk-delete-select-all");
            const checks = Array.from(
                table.querySelectorAll(".bulk-delete-row-check"),
            );
            const button = wrapper.querySelector(".bulk-delete-button");
            const count = wrapper.querySelector(".bulk-delete-count");

            const refreshState = () => {
                const total = checks.filter((check) => check.checked).length;
                button.disabled = total === 0;
                count.textContent = `${total} data dipilih`;
                selectAll.checked = total > 0 && total === checks.length;
                selectAll.indeterminate = total > 0 && total < checks.length;
            };

            selectAll.addEventListener("change", () => {
                checks.forEach((check) => {
                    check.checked = selectAll.checked;
                });
                refreshState();
            });

            checks.forEach((check) =>
                check.addEventListener("change", refreshState),
            );

            button.addEventListener("click", () => {
                const ids = checks
                    .filter((check) => check.checked)
                    .map((check) => check.value);
                if (!ids.length) {
                    return;
                }

                const submitBulkDelete = () => {
                    const form = document.createElement("form");
                    form.method = "POST";
                    form.action = "/dashboard/admin/bulk-delete";
                    form.className = "bulk-delete-form";

                    const csrf =
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "";
                    form.innerHTML = `
                        <input type="hidden" name="_token" value="${csrf}">
                        <input type="hidden" name="resource" value="${resource}">
                    `;

                    ids.forEach((id) => {
                        const input = document.createElement("input");
                        input.type = "hidden";
                        input.name = "ids[]";
                        input.value = id;
                        form.appendChild(input);
                    });

                    document.body.appendChild(form);
                    showAdminLoading("Data terpilih sedang dihapus.");
                    form.submit();
                };

                showAdminConfirm({
                    title: "Hapus Data Terpilih?",
                    message: `${ids.length} data akan dihapus permanen dari sistem dan tidak masuk ke arsip.`,
                    confirmText: "Ya, hapus",
                    danger: true,
                }).then((confirmed) => {
                    if (confirmed) {
                        submitBulkDelete();
                    }
                });
            });

            table.dataset.bulkDeleteIndex = tableIndex;
        });
    };

    enhanceBulkDeleteTables();

    if (isAdminPage) {
        document
            .querySelectorAll('a[href*="/delete/"], a[href*="reset-password"]')
            .forEach((link) => {
                if (link.dataset.confirm) {
                    return;
                }

                const isDelete = link.href.includes("/delete/");
                link.dataset.confirm = isDelete
                    ? "Data akan dihapus permanen dari sistem dan tidak masuk ke arsip."
                    : "Lanjutkan ke proses atur ulang kata sandi akun ini?";
            });
    }

    document.querySelectorAll("[data-confirm]").forEach((element) => {
        element.addEventListener("click", (event) => {
            if (!isAdminPage) {
                if (!confirm(element.dataset.confirm)) {
                    event.preventDefault();
                }
                return;
            }

            event.preventDefault();

            showAdminConfirm({
                title: element.href?.includes("/delete/")
                    ? "Hapus Data?"
                    : "Konfirmasi Aksi",
                message: element.dataset.confirm || "Lanjutkan aksi ini?",
                confirmText: element.href?.includes("/delete/")
                    ? "Ya, hapus"
                    : "Ya, lanjutkan",
                danger:
                    element.href?.includes("/delete/") ||
                    element.classList.contains("danger") ||
                    element.classList.contains("hapus"),
            }).then((confirmed) => {
                if (!confirmed) {
                    return;
                }

                showAdminLoading("Perubahan sedang disimpan.");

                if (element.tagName === "A") {
                    window.location.href = element.href;
                    return;
                }

                const form = element.closest("form");
                if (form?.requestSubmit) {
                    form.requestSubmit(element);
                } else {
                    form?.submit();
                }
            });
        });
    });

    const sidebarToggle = document.querySelector("[data-toggle-sidebar]");
    const sidebar = document.getElementById("sidebar");

    if (sidebar && window.matchMedia("(max-width: 900px)").matches) {
        sidebar.classList.add("close");
    }

    const links = Array.from(sidebar?.querySelectorAll("a[href]") || []).filter(
        (link) => link.getAttribute("href") !== "/logout",
    );

    const activeLink =
        links
            .filter((link) => {
                const href = link.getAttribute("href");

                return (
                    window.location.pathname === href ||
                    (href !== "/dashboard/admin" &&
                        window.location.pathname.startsWith(href + "/"))
                );
            })
            .sort(
                (a, b) =>
                    b.getAttribute("href").length -
                    a.getAttribute("href").length,
            )[0] ||
        links.find(
            (link) => link.getAttribute("href") === window.location.pathname,
        );

    activeLink?.classList.add("active");

    document.querySelectorAll("[data-sidebar-parent]").forEach((button) => {
        const submenu = button.nextElementSibling;
        if (!submenu?.classList.contains("sidebar-submenu")) {
            return;
        }

        const setOpen = (open) => {
            button.classList.toggle("is-open", open);
            submenu.classList.toggle("is-open", open);
            button.setAttribute("aria-expanded", open ? "true" : "false");
        };

        setOpen(Boolean(submenu.querySelector("a.active")));

        button.addEventListener("click", () => {
            setOpen(!submenu.classList.contains("is-open"));
        });
    });

    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", () => {
            sidebar?.classList.toggle("close");
            document.getElementById("content")?.classList.toggle("full");
        });
    }

    if (isAdminPage) {
        const alerts = Array.from(document.querySelectorAll(".app-alert"));

        if (alerts.length) {
            const firstAlert = alerts[0];
            const isSuccess = firstAlert.classList.contains("success");
            const message = firstAlert.textContent.trim();
            const isHolidayWarning = message.toLowerCase().includes("libur");

            document.querySelector(".app-alerts")?.remove();

            showAdminToast({
                type: isSuccess ? "success" : "error",
                title: isSuccess
                    ? "Berhasil"
                    : isHolidayWarning
                      ? "Hari Libur"
                      : "Perlu Dicek",
                message,
                timeout: isSuccess ? 3000 : 6000,
            });
        }
    }

    document.querySelectorAll("[data-auto-dismiss]").forEach((alert) => {
        window.setTimeout(() => {
            alert.classList.add("is-hiding");
            window.setTimeout(() => alert.remove(), 240);
        }, 4800);
    });

    if (isAdminPage) {
        document
            .querySelectorAll('form[method="POST"], form[method="post"]')
            .forEach((form) => {
                form.addEventListener("submit", (event) => {
                    if (event.defaultPrevented) {
                        return;
                    }
                    showAdminLoading("Data sedang diproses dan disimpan.");
                });
            });
    }

    window.printReport = (title = document.title) => {
        document.body.dataset.printTitle = title;
        window.print();
    };

    window.exportTableToExcel = (
        filename = "rekap-data",
        title = document.querySelector("main")?.dataset.printTitle ||
            document.title,
    ) => {
        const table = document.querySelector("main table");

        if (!table) {
            alert("Tabel rekap tidak ditemukan.");
            return;
        }

        const clonedTable = table.cloneNode(true);
        clonedTable
            .querySelectorAll("a, button")
            .forEach((element) => element.remove());
        clonedTable.querySelectorAll("th").forEach((cell) => {
            cell.setAttribute(
                "style",
                "background:#273c75;color:#ffffff;font-weight:bold;border:1px solid #8ea0c9;padding:8px;text-align:center;",
            );
        });
        clonedTable.querySelectorAll("td").forEach((cell) => {
            cell.setAttribute(
                "style",
                "border:1px solid #cbd5e1;padding:7px;vertical-align:top;",
            );
        });
        clonedTable.setAttribute(
            "style",
            "border-collapse:collapse;width:100%;font-family:Arial,sans-serif;font-size:12px;",
        );

        const printedAt = new Date().toLocaleString("id-ID", {
            day: "2-digit",
            month: "long",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });

        const html = `
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body { font-family: Arial, sans-serif; color:#111827; }
                    .report-title { font-size:20px; font-weight:700; text-align:center; margin-bottom:4px; }
                    .report-meta { font-size:12px; color:#475569; text-align:center; margin-bottom:14px; }
                    .report-note { font-size:11px; color:#64748b; margin-top:12px; }
                </style>
            </head>
            <body>
                <div class="report-title">${title}</div>
                <div class="report-meta">Dicetak: ${printedAt}</div>
                ${clonedTable.outerHTML}
                <div class="report-note">Diekspor dari sistem Absensi QR.</div>
            </body>
            </html>
        `;
        const blob = new Blob([html], { type: "application/vnd.ms-excel" });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = `${filename}.xls`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    };
});
