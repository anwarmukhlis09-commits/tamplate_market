document.addEventListener("DOMContentLoaded", () => {
    // --- Check for MikroTik error on page load ---
    const mtError = document.getElementById("mikrotik-error");
    if (mtError) {
        const errorText = mtError.textContent.toLowerCase();
        if (errorText.includes("trial") && (errorText.includes("limit") || errorText.includes("reached") || errorText.includes("time") || errorText.includes("not allowed") || errorText.includes("invalid") || errorText.includes("user"))) {
            mtError.textContent = "Free Trial sudah digunakan atau telah habis. Silakan login menggunakan akun atau voucher.";
        }
    }

    // --- Tabs Navigation Logic ---
    const tabMember = document.getElementById("tab-member");
    const tabVoucher = document.getElementById("tab-voucher");

    const usernameInput = document.getElementById("auth-username");
    const passwordGroup = document.getElementById("password-group");
    const showPasswordGroup = document.getElementById("show-password-group");

    const activeTabBgColor = tabMember.getAttribute("data-edit-bg-color") || "#ffffff";
    const activeTabTextColor = tabMember.getAttribute("data-edit-color") || "#1e293b";

    let activeTab = "member"; // default

    function switchTab(tab) {
        // Reset styles for both tabs
        [tabMember, tabVoucher].forEach(btn => {
            btn.classList.remove("active");
            btn.style.backgroundColor = "transparent";
            btn.style.color = "#64748b"; // muted gray
        });

        activeTab = tab;

        if (tab === "member") {
            tabMember.classList.add("active");
            tabMember.style.backgroundColor = activeTabBgColor;
            tabMember.style.color = activeTabTextColor;
            
            // Show password elements
            passwordGroup.style.display = "block";
            showPasswordGroup.style.display = "flex";
            
            // Change username details
            usernameInput.placeholder = usernameInput.getAttribute("data-edit-placeholder") || "Username";
            if (usernameInput.value === "" || usernameInput.value.startsWith("VCH-")) {
                usernameInput.value = "Pondenet";
            }
        } else if (tab === "voucher") {
            tabVoucher.classList.add("active");
            tabVoucher.style.backgroundColor = activeTabBgColor;
            tabVoucher.style.color = activeTabTextColor;
            
            // Hide password elements
            passwordGroup.style.display = "none";
            showPasswordGroup.style.display = "none";
            
            // Change username details for voucher
            usernameInput.placeholder = "Masukkan voucher";
            if (usernameInput.value === "Pondenet") {
                usernameInput.value = "";
            }
        }
    }

    tabMember.addEventListener("click", () => switchTab("member"));
    tabVoucher.addEventListener("click", () => switchTab("voucher"));

    // Set initial active state
    switchTab("member");

    // --- Show/Hide Password Toggle ---
    const chkShowPassword = document.getElementById("chk-show-password");
    const passwordInput = document.getElementById("auth-password");

    chkShowPassword.addEventListener("change", () => {
        if (chkShowPassword.checked) {
            passwordInput.type = "text";
        } else {
            passwordInput.type = "password";
        }
    });

    // --- Form Submission Logic ---
    const form = document.getElementById("hotspot-form");
    const hiddenUsername = document.getElementById("hidden-username");
    const hiddenPassword = document.getElementById("hidden-password");
    const simulatedError = document.getElementById("simulated-error");

    form.addEventListener("submit", (e) => {
        simulatedError.style.display = "none";

        if (activeTab === "member") {
            const user = usernameInput.value.trim();
            const pass = passwordInput.value.trim();
            if (!user || !pass) {
                e.preventDefault();
                showError("Username dan Password tidak boleh kosong!");
                return;
            }
            hiddenUsername.value = user;
            hiddenPassword.value = pass;
        } else if (activeTab === "voucher") {
            const code = usernameInput.value.trim();
            if (!code) {
                e.preventDefault();
                showError("Silakan masukkan kode voucher Anda!");
                return;
            }
            hiddenUsername.value = code;
            hiddenPassword.value = code; // Copy code to password for single input
        }

        // Mock action check
        const action = form.getAttribute("action");
        const isSimulated = action === "$(link-login-only)" || action === "http://example.com/login" || window.location.protocol === "file:";
        
        if (isSimulated) {
            e.preventDefault();
            simulateConnection(hiddenUsername.value, hiddenPassword.value, "status.html");
        }
    });

    // Connection simulation function
    function simulateConnection(username, password, redirectUrl) {
        const loaderOverlay = document.getElementById("loader-overlay");
        const loaderBar = document.getElementById("loader-bar");
        const loaderStatus = document.getElementById("loader-status");
        
        loaderOverlay.style.display = "flex";
        loaderBar.style.width = "0%";
        
        const steps = [
            { width: "25%", text: "Memverifikasi perangkat..." },
            { width: "55%", text: "Mengautentikasi pengguna..." },
            { width: "85%", text: "Mengalokasikan alamat IP..." },
            { width: "100%", text: "Koneksi Berhasil!" }
        ];
        
        let stepIndex = 0;
        const interval = setInterval(() => {
            if (stepIndex < steps.length) {
                loaderBar.style.width = steps[stepIndex].width;
                loaderStatus.textContent = steps[stepIndex].text;
                stepIndex++;
            } else {
                clearInterval(interval);
                setTimeout(() => {
                    loaderOverlay.style.display = "none";
                    window.location.href = redirectUrl;
                }, 800);
            }
        }, 500);
    }

    function showError(msg) {
        simulatedError.textContent = msg;
        simulatedError.style.display = "block";
        simulatedError.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // --- QR Code Scanner Simulation ---
    const qrBtn = document.getElementById("btn-qr");
    const qrModal = document.getElementById("qr-modal");
    const closeModal = document.querySelector(".close-modal");
    const btnScanSim = document.getElementById("btn-scan-sim");

    if (qrBtn) {
        qrBtn.addEventListener("click", () => {
            qrModal.style.display = "flex";
        });
    }

    if (closeModal) {
        closeModal.addEventListener("click", () => {
            qrModal.style.display = "none";
        });
    }

    window.addEventListener("click", (e) => {
        if (e.target === qrModal) {
            qrModal.style.display = "none";
        }
    });

    if (btnScanSim) {
        btnScanSim.addEventListener("click", () => {
            // Fill voucher, switch to voucher tab, and close modal
            switchTab("voucher");
            usernameInput.value = "VCH-PONDENET-" + Math.floor(1000 + Math.random() * 9000);
            qrModal.style.display = "none";
            // Focus and highlight
            usernameInput.focus();
            usernameInput.style.borderColor = "#006fe6";
            setTimeout(() => {
                usernameInput.style.borderColor = "";
            }, 1500);
        });
    }

    // --- Free Trial Link Trigger ---
    const trialLink = document.getElementById("link-trial");
    if (trialLink) {
        trialLink.addEventListener("click", (e) => {
            e.preventDefault();
            const action = form.getAttribute("action");
            const isSimulated = action === "$(link-login-only)" || action === "http://example.com/login" || window.location.protocol === "file:";
            
            if (isSimulated) {
                simulateConnection("trial", "", "status.html");
            } else {
                // Real MikroTik env - show a quick loader before redirecting
                const loaderOverlay = document.getElementById("loader-overlay");
                const loaderBar = document.getElementById("loader-bar");
                const loaderStatus = document.getElementById("loader-status");
                
                loaderOverlay.style.display = "flex";
                loaderBar.style.width = "100%";
                loaderStatus.textContent = "Connecting to Free Trial...";
                
                setTimeout(() => {
                    window.location.href = trialUrl;
                }, 600);
            }
        });
    }

    // --- Info Modals (About us, Package, Services) ---
    const infoModal = document.getElementById("info-modal");
    const closeInfoModal = document.getElementById("close-info-modal");
    const btnInfoClose = document.getElementById("btn-info-close");
    const infoTitle = document.getElementById("info-modal-title");
    const infoBody = document.getElementById("info-modal-body");

    function showInfoModal(title, htmlContent) {
        infoTitle.textContent = title;
        infoBody.innerHTML = htmlContent;
        infoModal.style.display = "flex";
    }

    function closeInfo() {
        infoModal.style.display = "none";
    }

    if (closeInfoModal) closeInfoModal.addEventListener("click", closeInfo);
    if (btnInfoClose) btnInfoClose.addEventListener("click", closeInfo);
    window.addEventListener("click", (e) => {
        if (e.target === infoModal) closeInfo();
    });

    const aboutLinks = document.querySelectorAll('a[href="#about"]');
    const packageLinks = document.querySelectorAll('a[href="#package"]');
    const servicesLinks = document.querySelectorAll('a[href="#services"]');

    aboutLinks.forEach(link => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            showInfoModal("About PondeNet", `
                <p><strong>PondeNet</strong> adalah layanan penyedia internet nirkabel (WiFi Hotspot) berkecepatan tinggi yang dirancang untuk kebutuhan rumahan, belajar, bermain game, dan bekerja.</p>
                <p style="margin-top: 10px;">Layanan kami didukung oleh serat optik berkapasitas besar dan router handal untuk memastikan kestabilan koneksi Anda selama 24 jam penuh.</p>
            `);
        });
    });

    packageLinks.forEach(link => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            showInfoModal("Daftar Paket Internet", `
                <p style="margin-bottom: 12px;">Pilihlah paket internet voucher yang sesuai dengan kebutuhan Anda:</p>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); font-weight: bold;">
                            <th style="padding: 6px 0; text-align: left;">Paket</th>
                            <th style="padding: 6px 0; text-align: right;">Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 8px 0;">Voucher 1 Jam (Speed 5Mbps)</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: 600; color: var(--primary);">Rp 2.000</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 8px 0;">Voucher 5 Jam (Speed 5Mbps)</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: 600; color: var(--primary);">Rp 5.000</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 8px 0;">Voucher 24 Jam (Speed 10Mbps)</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: 600; color: var(--primary);">Rp 10.000</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 8px 0;">Voucher Mingguan (Speed 10Mbps)</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: 600; color: var(--primary);">Rp 30.000</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0;">Voucher Bulanan (Speed 15Mbps)</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: 600; color: var(--primary);">Rp 100.000</td>
                        </tr>
                    </tbody>
                </table>
            `);
        });
    });

    servicesLinks.forEach(link => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            showInfoModal("Layanan Kami", `
                <ul style="padding-left: 20px;">
                    <li style="margin-bottom: 8px;"><strong>Koneksi Cepat & Stabil</strong>: Bandwidth yang diatur secara adil agar aktivitas streaming dan gaming berjalan lancar.</li>
                    <li style="margin-bottom: 8px;"><strong>Tanpa Batas Kuota (FUP)</strong>: Bebas unduh file apa saja tanpa khawatir kehabisan kuota atau penurunan kecepatan mendadak.</li>
                    <li style="margin-bottom: 8px;"><strong>Login Mudah</strong>: Cukup masukkan kode voucher atau scan kode QR langsung menggunakan kamera smartphone Anda.</li>
                    <li><strong>Dukungan 24/7</strong>: Hubungi admin kami melalui WhatsApp jika Anda mengalami kendala koneksi kapan saja.</li>
                </ul>
            `);
        });
    });
});
