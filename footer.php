<style>
/* =========================================================
   CUSTOM eREGISTRAR PROFESSIONAL FOOTER
   ========================================================= */
.registrar-footer {
    background-color: #0b1a30; /* A distinct deep collegiate tone */
    color: #94a3b8;
    padding: 70px 48px 30px 48px;
    border-top: 3px solid var(--bright-blue, #3982DC);
    font-size: 0.92rem;
}

.registrar-footer-container {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1.2fr;
    gap: 40px;
    padding-bottom: 40px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.registrar-footer-brand .brand-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.registrar-footer-brand .brand-header img {
    width: 40px;
    height: 40px;
    object-fit: contain;
}

.registrar-footer-brand h2 {
    color: #ffffff;
    font-size: 1.3rem;
    font-weight: 800;
    margin: 0;
    letter-spacing: -0.02em;
}

.registrar-footer-brand small {
    display: block;
    color: var(--sky-blue, #75AAE7);
    font-size: 0.78rem;
    font-weight: 600;
}

.registrar-footer-brand p {
    color: #94a3b8;
    line-height: 1.6;
    margin: 0 0 24px 0;
    font-size: 0.93rem;
}

.registrar-badge-box {
    background: rgba(57, 130, 220, 0.08);
    border: 1px solid rgba(57, 130, 220, 0.2);
    border-radius: 12px;
    padding: 14px 18px;
}

.registrar-badge-box .badge-title {
    color: #ffffff;
    font-weight: 700;
    font-size: 0.85rem;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.registrar-badge-box p {
    margin: 0;
    font-size: 0.82rem;
    color: #cbd5e1;
}

.registrar-footer-col h3 {
    color: #ffffff;
    font-size: 0.9rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin: 0 0 20px 0;
}

.registrar-footer-col ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.registrar-footer-col ul li a {
    color: #94a3b8;
    text-decoration: none;
    transition: color 0.2s;
    font-weight: 500;
}

.registrar-footer-col ul li a:hover {
    color: var(--sky-blue, #75AAE7);
}

.registrar-footer-col .contact-item {
    margin: 0 0 14px 0;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    color: #94a3b8;
    line-height: 1.5;
    font-size: 0.9rem;
}

.registrar-footer-col .contact-item i {
    color: var(--bright-blue, #3982DC);
    margin-top: 4px;
}

.registrar-footer-bottom {
    max-width: 1200px;
    margin: 30px auto 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 0.85rem;
    color: #64748b;
}

.registrar-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.25);
    color: #60a5fa;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
}

.registrar-status-pill .live-dot {
    width: 7px;
    height: 7px;
    background-color: #60a5fa;
    border-radius: 50%;
    box-shadow: 0 0 8px #60a5fa;
}

@media (max-width: 968px) {
    .registrar-footer-container {
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
    .registrar-footer {
        padding: 40px 24px 24px 24px;
    }
}

@media (max-width: 580px) {
    .registrar-footer-container {
        grid-template-columns: 1fr;
    }
}
</style>

<footer class="registrar-footer">
    <div class="registrar-footer-container">
        
        <!-- Brand & Mission Column -->
        <div class="registrar-footer-brand">
            <div class="brand-header">
                <img src="assets/images/logo1.png" alt="eRegistrar Logo">
                <div>
                    <h2>eRegistrar</h2>
                    <small>Consolatrix College of Toledo City</small>
                </div>
            </div>
            <p>
                Streamlining academic credential requests, document verification, and clearance tracking for students and alumni.
            </p>
            <div class="registrar-badge-box">
                <div class="badge-title">
                    <i class="fa-solid fa-circle-check" style="color: var(--bright-blue, #3982DC);"></i> Official Online Portal
                </div>
                <p>Authorized digital document processing platform managed by the Registrar Office.</p>
            </div>
        </div>

        <!-- Quick Links Column -->
        <div class="registrar-footer-col">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="index.php">Home Page</a></li>
                <li><a href="about.php">About eRegistrar</a></li>
                <li><a href="how-it-works.php">Request Guidelines</a></li>
                <li><a href="contact.php">Help & Support</a></li>
            </ul>
        </div>

        <!-- Document Services Column -->
        <div class="registrar-footer-col">
            <h3>Services</h3>
            <ul>
                <li><a href="login.php">Transcript of Records</a></li>
                <li><a href="login.php">Good Moral Certificate</a></li>
                <li><a href="login.php">Diploma Replacement</a></li>
                <li><a href="login.php">Authentication</a></li>
            </ul>
        </div>

        <!-- Office Details Column -->
        <div class="registrar-footer-col">
            <h3>Registrar Office</h3>
            <div class="contact-item">
                <i class="fa-solid fa-location-dot"></i>
                <span>Administration Bldg., CCTC Campus, Toledo City, Cebu</span>
            </div>
            <div class="contact-item">
                <i class="fa-solid fa-envelope"></i>
                <span>admin@consolatrix.edu</span>
            </div>
            <div class="contact-item">
                <i class="fa-solid fa-clock"></i>
                <span>Mon – Fri: 8:00 AM – 5:00 PM</span>
            </div>
        </div>

    </div>

    <!-- Bottom Bar -->
    <div class="registrar-footer-bottom">
        <p>&copy; 2026 eRegistrar. Consolatrix College of Toledo City. All rights reserved.</p>
        <div class="registrar-status-pill">
            <span class="live-dot"></span> Online Request Portal Active
        </div>
    </div>
</footer>