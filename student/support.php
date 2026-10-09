<?php
session_start();
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eRegistrar | Help & Support</title>

    <link rel="icon" type="image/png" href="../assets/images/logooo.png?v=3">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student.css">

    <style>
        :root {
            --primary: #0056b3;
            --primary-hover: #004494;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text);
            min-height: 100vh;
        }

        .student-main {
            max-width: 900px;
            width: 100%;
            margin: 30px auto;
            padding: 0 20px 60px;
        }

        /* Prevent student.css from turning text upside down */
        .faq-item, .faq-question, .faq-answer {
            transform: none !important;
            writing-mode: horizontal-tb !important;
        }

        .help-header {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .help-header-text h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 4px;
        }

        .help-header-text p {
            color: var(--text-muted);
            font-size: 14px;
        }

        .help-button {
            background-color: #eff6ff;
            color: var(--primary);
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .help-button:hover {
            background-color: var(--primary);
            color: #ffffff;
        }

        /* Category Tabs */
        .help-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            overflow-x: auto;
            padding-bottom: 4px;
        }

        .help-tab {
            background: #ffffff;
            border: 1px solid var(--border);
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .help-tab.active {
            background-color: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        /* FAQ Items */
        .faq-container {
            display: none;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 30px;
        }

        .faq-container.active {
            display: flex;
        }

        .faq-item {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .faq-question {
            padding: 18px 20px;
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            transform: none !important;
        }

        .faq-answer {
            display: none;
            background: #f8fafc;
            color: #475569;
            font-size: 13.5px;
            line-height: 1.6;
            padding: 16px 20px;
            border-top: 1px solid var(--border);
            transform: none !important;
        }

        /* Renamed toggle class from 'open' to 'is-active' to prevent CSS conflicts */
        .faq-item.is-active .faq-answer {
            display: block !important;
        }

        /* Contact Box */
        .contact-box {
            background: linear-gradient(135deg, #1e3a8a 0%, #0056b3 100%);
            color: #ffffff;
            border-radius: 16px;
            padding: 32px 28px;
            text-align: center;
        }

        .contact-box h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .contact-box p {
            font-size: 14px;
            color: #e0eeff;
            margin-bottom: 20px;
        }

        .contact-box a {
            background-color: #ffffff;
            color: var(--primary);
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        @media (max-width: 600px) {
            .student-main {
                margin: 15px auto;
                padding: 0 14px 80px;
            }

            .help-header {
                padding: 20px;
                flex-direction: column;
                align-items: flex-start;
            }

            .help-button, .contact-box a {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <!-- Include Navbar -->
    <?php require_once "navbar.php"; ?>

    <div class="student-main">

        <!-- Page Header -->
        <div class="help-header">
            <div class="help-header-text">
                <h1><i class="fa-solid fa-circle-question"></i> Help & Support</h1>
                <p>Find answers, manage your account, and contact the Registrar for assistance.</p>
            </div>
            <a href="messages.php" class="help-button">
                <i class="fa-solid fa-headset"></i> Need More Help?
            </a>
        </div>

        <!-- Category Tabs -->
        <div class="help-tabs">
            <button class="help-tab active" data-target="account">
                <i class="fa-solid fa-user-shield"></i> Account Problems
            </button>
            <button class="help-tab" data-target="portal">
                <i class="fa-solid fa-graduation-cap"></i> Student Portal
            </button>
            <button class="help-tab" data-target="payment">
                <i class="fa-solid fa-wallet"></i> Payments
            </button>
            <button class="help-tab" data-target="document">
                <i class="fa-solid fa-file-invoice"></i> Documents
            </button>
        </div>

        <!-- ACCOUNT FAQ -->
        <div class="faq-container active" id="account">
            <div class="faq-item">
                <div class="faq-question">
                    <span>I forgot my password. What should I do?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Use the "Forgot Password" option on the login page to reset your account password. If you still cannot access your account, contact the Registrar Office directly.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How can I update my profile?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Go to My Profile to update your personal information and profile picture.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How will I know if the Registrar replied?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Check the Messages section of your account to view replies from the Registrar.
                </div>
            </div>
        </div>

        <!-- STUDENT PORTAL FAQ -->
        <div class="faq-container" id="portal">
            <div class="faq-item">
                <div class="faq-question">
                    <span>How do I submit a document request?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Open Request Document from the navigation menu, select your document, fill in the required information, and submit.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How do I check my notifications?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Notifications from the Registrar can be viewed from your main dashboard.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How do I use the Student Portal?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Use the menu to access requests, payments, documents, messages, and settings.
                </div>
            </div>
        </div>

        <!-- PAYMENT FAQ -->
        <div class="faq-container" id="payment">
            <div class="faq-item">
                <div class="faq-question">
                    <span>Why can't I upload my payment proof?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Make sure your file format is accepted and the file size does not exceed the allowed limit.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Why is my payment still pending?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Payments must be checked and verified by the Registrar Office.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How can I check my payment status?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Open the Payments page to view your current payment status.
                </div>
            </div>
        </div>

        <!-- DOCUMENT FAQ -->
        <div class="faq-container" id="document">
            <div class="faq-item">
                <div class="faq-question">
                    <span>How can I request documents?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Go to Request Document, choose your document, complete the request form, and submit.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How can I track my document?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Open Track Document to view the progress of your request.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How long does document processing take?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Processing time depends on the document type and Registrar approval.
                </div>
            </div>
        </div>

        <!-- Contact Box -->
        <div class="contact-box">
            <h2>Need More Help?</h2>
            <p>For document requests, payments, account concerns, and other issues, contact the Registrar Office.</p>
            <a href="messages.php">
                <i class="fa-solid fa-paper-plane"></i> Contact Registrar
            </a>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Category Tabs
            const tabs = document.querySelectorAll(".help-tab");
            const containers = document.querySelectorAll(".faq-container");

            tabs.forEach(tab => {
                tab.addEventListener("click", () => {
                    tabs.forEach(t => t.classList.remove("active"));
                    containers.forEach(c => c.classList.remove("active"));

                    tab.classList.add("active");
                    const target = tab.dataset.target;
                    const activeContainer = document.getElementById(target);
                    if (activeContainer) {
                        activeContainer.classList.add("active");
                    }
                });
            });

            // Toggle Open/Close FAQ using 'is-active' instead of 'open'
            const faqQuestions = document.querySelectorAll(".faq-question");
            faqQuestions.forEach(question => {
                question.addEventListener("click", () => {
                    const item = question.parentElement;
                    item.classList.toggle("is-active");
                });
            });
        });
    </script>

</body>
</html>