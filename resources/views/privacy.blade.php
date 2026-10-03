<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Statement · E-LIKAS</title>
    <meta name="description" content="Privacy Statement for E-LIKAS, an academic capstone project for disaster evacuation management developed in partnership with CSWDO Ligao City: what evacuee information is recorded, how long it is kept, and who can access it.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { DEFAULT: '#2F5496', dark: '#1F3A6E' } } } } };
    </script>
    <style>body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; }</style>
</head>
<body class="bg-gray-50">
    <div class="max-w-2xl mx-auto px-6 py-12">
        <p class="text-brand font-bold text-2xl mb-1">E-LIKAS</p>
        <p class="text-sm text-gray-500 mb-8">Electronic Ligao Kaligtasan Sistema — Privacy Statement</p>

        <div class="bg-white border border-gray-200 rounded-xl p-8 space-y-6 text-sm text-gray-700 leading-relaxed">

            <section>
                <h2 class="font-semibold text-gray-900 mb-2">About This System</h2>
                <p>
                    E-LIKAS is a Bachelor of Science in Information Technology academic
                    capstone project, developed in partnership with the City Social Welfare
                    and Development Office (CSWDO) of Ligao City, which cooperated with the
                    study and provided information supporting its development. It supports
                    evacuation center management, evacuee registration, disaster alerting,
                    and related disaster response coordination.
                </p>
            </section>

            <section>
                <h2 class="font-semibold text-gray-900 mb-2">Information We Collect and How We Use It</h2>
                <p>
                    When a family is registered as displaced, E-LIKAS records each
                    member's name, exact date of birth, sex, civil status, and contact
                    number where provided, along with the family's home address. Depending
                    on how a family is registered, some of this information -- such as age
                    bracket and sex, or sectoral details including whether the household
                    has a single head, or whether a member is a person with disability,
                    pregnant, or a solo parent -- may initially be recorded as an aggregate
                    figure before an individual's full details are entered. This
                    information is used to coordinate evacuation center operations,
                    allocate relief assistance appropriately, and prepare official disaster
                    reports for submission to the City Social Welfare and Development
                    Office (CSWDO) and, where required, to the Department of Social Welfare
                    and Development (DSWD).
                </p>
            </section>

            <section>
                <h2 class="font-semibold text-gray-900 mb-2">How Long It Is Kept</h2>
                <p>
                    This information is retained as a historical record even after a
                    disaster event has concluded. Evacuation records are not deleted or
                    archived out of the system when an event is closed; they remain part of
                    the system's permanent record so that official reports can still be
                    generated or referenced afterward, consistent with standard government
                    recordkeeping practice for disaster response documentation.
                </p>
            </section>

            {{-- Deliberately says nothing yet about barangay officials being
                limited to their own barangay: that isn't true until the EC
                Board's cross-barangay access is fixed, and the wording for it
                comes after that fix is verified. --}}
            <section>
                <h2 class="font-semibold text-gray-900 mb-2">Who Can Access This Information</h2>
                <p>
                    Access to evacuee information is restricted to authorized CSWDO
                    personnel and Barangay Officials. Residents using the system's
                    public-facing features have no access to register, view, or search
                    any evacuee's personal information.
                </p>
            </section>

            <section>
                <h2 class="font-semibold text-gray-900 mb-2">How We Protect This Information</h2>
                <p>
                    The system is served over an encrypted (HTTPS) connection, access
                    requires individual authentication, and administrative actions are
                    logged. As an actively developed system, security measures continue
                    to be reviewed and improved.
                </p>
            </section>

            <section>
                <h2 class="font-semibold text-gray-900 mb-2">Your Rights</h2>
                <p>
                    Under Republic Act No. 10173, the Data Privacy Act of 2012, you have
                    the right to be informed about how your personal data is processed,
                    to access your own data, to request correction of inaccurate data, and
                    to file a complaint with the National Privacy Commission. To exercise
                    these rights regarding information collected through E-LIKAS, contact
                    CSWDO Ligao City directly.
                </p>
            </section>

            <section>
                <h2 class="font-semibold text-gray-900 mb-2">Contact</h2>
                <p>
                    For questions about this privacy statement or your information, contact
                    the City Social Welfare and Development Office, Ligao City, Albay.
                </p>
            </section>

            <section class="pt-4 border-t border-gray-100">
                <p class="text-xs text-gray-500">
                    E-LIKAS is a Bachelor of Science in Information Technology academic
                    capstone project, developed in partnership with the City Social Welfare
                    and Development Office (CSWDO) of Ligao City, which cooperated with the
                    study and provided information supporting its development. It has not
                    been operationally adopted by CSWDO for actual disaster response
                    operations.
                </p>
            </section>

        </div>
    </div>
</body>
</html>
