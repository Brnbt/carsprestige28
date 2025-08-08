    <style>
        footer {
            background-color: black;
            color: #fff;
            padding: 40px 0;
        }

        .footer-container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px; /* Added padding for mobile screens */
        }

        .footer-section {
            flex: 1 1 300px;
            margin-bottom: 30px;
        }

        .footer-section h3 {
            font-size: 18px;
            margin-bottom: 15px;
        }

        .social-links a {
            color: #fff;
            font-size: 24px;
            margin-right: 10px;
            transition: color 0.3s ease;
        }

        .social-links a:hover {
            color: #daf37c;
        }

        .footer-section ul {
            list-style-type: none;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: 10px;
        }

        .footer-section ul li a {
            color: #fff;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: #daf37c;
        }

        .copyright {
            text-align: center;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .footer-section {
                flex-basis: 100%;
                margin-bottom: 20px; /* Adjusted margin for better spacing */
            }

            .footer-container {
                padding: 0 10px; /* Further padding for smaller screens */
            }
        }

        @media (max-width: 768px) {
    footer {
        display: none;
    }
}

    </style>
    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h3>Suivez-nous</h3>
                <div class="social-links">
                    <a href="https://discord.gg/3Fdabyy3Bz" target="_blank"><i class="bx bxl-discord"></i></a>
                    <a href="https://twitter.com/TNK_inside" target="_blank"><i class="bx bxl-twitter"></i></a>
                    <a href="https://www.tiktok.com/@tnk_inside" target="_blank"><i class="bx bxl-tiktok"></i></a>
                    <a href="https://www.youtube.com/channel/UClI18ijPzgGVi2TUr6sg5kg" target="_blank"><i class="bx bxl-youtube"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Liens Utiles</h3>
                <ul>
                    <li><a href="mentions_legales">Mentions Légales</a></li>
                    <li><a href="aide">Aides</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contactez-nous</h3>
                <p>Email: tanukiteam95100@gmail.com</p>
            </div>
        </div>
        <div class="copyright">
            <p>© 2025 Cars Prestige 28 Tous droits réservés</p>
        </div>
    </footer>
</body>
</html>
