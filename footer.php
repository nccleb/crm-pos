<footer class="modern-footer">
    <div class="footer-content">
        <div class="footer-section">
            <div class="footer-logo">
                <i class="fas fa-laptop-code"></i>
                <span class="company-name">Nahed Computers & Communications</span>
            </div>
            <p class="footer-tagline">Your trusted technology partner since 2018</p>
        </div>
        
        <div class="footer-section">
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-phone-alt"></i>
                    <span>+00 961 5 454262</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-mobile-alt"></i>
                    <span>+00 961 3 205818</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>nccleb@gmail.com</span>
                </div>
            </div>
        </div>
        
        <div class="footer-section">
            <div class="system-info">
                <div class="version-badge">
                    <i class="fas fa-code-branch"></i>
                    <span>NCCIS 1.7.2</span>
                </div>
                
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="footer-copyright">
            <i class="far fa-copyright"></i>
            <span>2018 - <?php echo date('Y'); ?> Nahed Computers & Communications. All rights reserved.</span>
        </div>
        <!--div class="footer-links">
            <a href="#" class="footer-link">Privacy Policy</a>
            <span class="separator">|</span>
            <a href="#" class="footer-link">Terms of Service</a>
            <span class="separator">|</span>
            <a href="#" class="footer-link">Support</a>
        </div-->
    </div>
</footer>

<style>
.modern-footer {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: #ecf0f1;
    padding: 30px 0 0 0;
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    border-top: 4px solid #3498db;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
}

.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px 20px 20px;
    flex-wrap: wrap;
    gap: 30px;
}

.footer-section {
    flex: 1;
    min-width: 250px;
}

.footer-logo {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.footer-logo i {
    font-size: 28px;
    color: #3498db;
    margin-right: 12px;
}

.company-name {
    font-size: 20px;
    font-weight: 700;
    color: #ecf0f1;
}

.footer-tagline {
    color: #bdc3c7;
    font-size: 14px;
    margin: 0;
    font-style: italic;
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 12px;
    transition: transform 0.2s ease;
}

.contact-item:hover {
    transform: translateX(5px);
}

.contact-item i {
    width: 20px;
    color: #3498db;
    font-size: 16px;
}

.contact-item span {
    color: #ecf0f1;
    font-size: 15px;
}

.system-info {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.version-badge, .status-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    background: rgba(52, 152, 219, 0.1);
    border-radius: 6px;
    border-left: 3px solid #3498db;
}

.version-badge i, .status-indicator i {
    color: #3498db;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 5px;
}

.status-dot.online {
    background: #2ecc71;
    box-shadow: 0 0 8px #2ecc71;
}

.footer-bottom {
    background: rgba(0, 0, 0, 0.3);
    padding: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.footer-copyright {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #bdc3c7;
    font-size: 14px;
}

.footer-links {
    display: flex;
    align-items: center;
    gap: 10px;
}

.footer-link {
    color: #bdc3c7;
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s ease;
}

.footer-link:hover {
    color: #3498db;
}

.separator {
    color: #7f8c8d;
    font-size: 12px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        text-align: center;
        gap: 25px;
    }
    
    .footer-logo {
        justify-content: center;
    }
    
    .contact-item {
        justify-content: center;
    }
    
    .footer-bottom {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .footer-links {
        justify-content: center;
    }
}

/* Animation for status indicator */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.status-dot.online {
    animation: pulse 2s infinite;
}
</style>