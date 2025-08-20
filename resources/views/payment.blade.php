<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Language') }} — {{ __('ui.subscribe_header') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-12">
    <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-2xl">
        <div class="p-8">
            @if (session('error'))
                <div class="mb-4 rounded bg-red-100 text-red-800 p-3">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded bg-red-100 text-red-800 p-3">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">{{ __('ui.subscribe_header') }}</h1>
                <p class="text-gray-600 mt-2">{{ __('ui.subscribe_text') }}</p>
            </div>

            @if ($user && $remainingDays > 0)
                <p class="text-green-600 font-medium mb-4">
                    {{ __('ui.already_subscribed', ['days' => $remainingDays]) }}
                </p>
            @elseif ($user && isset($remainingDays) && $remainingDays <= 0)
                <p class="text-red-600 font-medium mb-4">
                    {{ __('ui.subscription_expired', ['days' => abs($remainingDays)]) }}
                </p>
            @endif

            <form>
                <div id="client-error" class="mb-4 rounded bg-red-100 text-red-800 p-3 hidden"></div>
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">{{ __('ui.email') }}</label>
                    <input type="email" id="email" name="email" required
                           value="{{ request('email') }}"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           placeholder="your@email.com">
                </div>

                <div class="mb-6 space-y-4">
                    <div class="flex items-center">
                        <input id="monthly" name="plan" type="radio" value="monthly" checked
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                        <label for="monthly" class="ml-3 block text-gray-700">
                            <span class="font-medium">{{ __('ui.monthly') }}</span>
                            <span class="text-gray-600"> - $2.00 (30 days)</span>
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input id="yearly" name="plan" type="radio" value="yearly"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                        <label for="yearly" class="ml-3 block text-gray-700">
                            <span class="font-medium">{{ __('ui.yearly') }}</span>
                            <span class="text-gray-600"> - $12.00 (1 year)</span>
                        </label>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-3">
                    <button type="button" id="pay-aptos"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded focus:outline-none focus:shadow-outline">
                        Pay with Aptos (USDC)
                    </button>
                    <button type="button" id="pay-solana"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded focus:outline-none focus:shadow-outline">
                        Pay with Solana (USDC)
                    </button>
                    <p class="text-sm text-gray-500 text-center">USDC only. Your email and plan will be used to activate your subscription after on-chain payment.</p>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-8 max-w-2xl mx-auto bg-white p-8 rounded-xl shadow-lg">
        <h2 class="text-2xl font-bold mb-6 flex items-center">
            <span class="mr-2">{{ __('ui.sentence_today') }}</span>
        </h2>

        <div class="space-y-6">
            <div class="border-b border-gray-200 pb-4">
                <p class="font-semibold text-gray-700 mb-2">{{ __('ui.kanji') }}</p>
                <p class="text-2xl">{{ $sentence['kanji'] ?? '' }}</p>
            </div>
            <div class="border-b border-gray-200 pb-4">
                <p class="font-semibold text-gray-700 mb-2">{{ __('ui.hiragana') }}</p>
                <p class="text-xl">{{ $sentence['hiragana'] ?? '' }}</p>
            </div>
            <div class="border-b border-gray-200 pb-4">
                <p class="font-semibold text-gray-700 mb-2">{{ __('ui.romaji') }}</p>
                <p class="text-lg italic text-gray-600">{{ $sentence['romaji'] ?? '' }}</p>
            </div>
            <div class="border-b border-gray-200 pb-4">
                <p class="font-semibold text-gray-700 mb-2">{{ __('ui.meaning') }}</p>
                <p class="text-gray-700">{{ $sentence['meaning'] ?? '' }}</p>
            </div>
            <div class="border-b border-gray-200 pb-4">
                <p class="font-semibold text-gray-700 mb-2">{{ __('ui.breakdown') }}</p>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="whitespace-pre-line">{{ $sentence['breakdown'] ?? '' }}</p>
                </div>
            </div>
            <div class="border-b border-gray-200 pb-4">
                <p class="font-semibold text-gray-700 mb-2">{{ __('ui.grammar') }}</p>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="whitespace-pre-line">{{ $sentence['grammar'] ?? '' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="solana-modal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 hidden">
<div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl relative">
<button id="solana-modal-close" class="absolute right-3 top-3 text-gray-500 hover:text-gray-700" aria-label="Close">&times;</button>
<h3 class="text-lg font-semibold mb-2 text-center">Scan to Pay (Solana USDC)</h3>
<div id="solana-qr" class="flex justify-center"></div>
<div class="mt-4">
    <div class="text-xs text-gray-600">Amount: <span id="solana-amount"></span> USDC</div>
    <div class="text-xs text-gray-500">Reference: <span id="solana-ref"></span></div>
    <div class="text-[10px] text-gray-400 mt-1">USDC Mint: <span id="solana-mint"></span></div>
    <div class="mt-4 grid grid-cols-1 gap-2">
        <button id="open-phantom" class="w-full bg-violet-600 hover:bg-violet-700 text-white rounded py-2 text-sm">Open in Phantom</button>
        <button id="open-solflare" class="w-full bg-orange-600 hover:bg-orange-700 text-white rounded py-2 text-sm">Open in Solflare</button>
        <a id="open-phantom-mobile" class="w-full bg-violet-700 hover:bg-violet-800 text-white rounded py-2 text-sm text-center hidden" target="_blank" rel="noopener">Open in Phantom (Mobile)</a>
        <p class="text-[10px] text-gray-500 text-center">On desktop, these may not work unless a wallet is installed and registered. QR is the most reliable on mobile.</p>
    </div>
</div>
<div id="solana-status" class="mt-2 text-center text-gray-600 text-sm">Waiting for payment...</div>
</div>
</div>
<div id="aptos-modal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 hidden">
<div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl relative">
<button id="aptos-modal-close" class="absolute right-3 top-3 text-gray-500 hover:text-gray-700" aria-label="Close">&times;</button>
<h3 class="text-lg font-semibold mb-2 text-center">Scan to Pay (Aptos USDC)</h3>
<div id="aptos-qr" class="flex justify-center"></div>
<div class="mt-4">
    <div class="text-xs text-gray-600">Amount: <span id="aptos-amount"></span> USDC</div>
    <div class="text-xs text-gray-500">Reference: <span id="aptos-ref"></span></div>
    <div class="text-[10px] text-gray-400 mt-1">USDC Coin: <span id="aptos-coin"></span></div>
    <div id="aptos-buttons" class="mt-4 grid grid-cols-1 gap-2">
        <button id="open-petra" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded py-2 text-sm">Open in Petra</button>
        <button id="open-pontem" class="w-full bg-pink-600 hover:bg-pink-700 text-white rounded py-2 text-sm">Open in Pontem</button>
        <p class="text-[10px] text-gray-500 text-center">If buttons don’t open a wallet, scan the QR with your mobile wallet.</p>
    </div>
    <p class="text-[10px] text-gray-500 text-center mt-3">Open your Aptos wallet and scan the QR. Deeplinks vary by wallet; QR is recommended.</p>
    <div id="aptos-status" class="mt-2 text-center text-gray-600 text-sm">Waiting for payment...</div>
</div>
</div>
<!-- QR Code lib -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<!-- Solana libs for desktop extension flow -->
<script src="https://unpkg.com/@solana/web3.js@1.95.3/lib/index.iife.min.js"></script>
<script src="https://unpkg.com/@solana/spl-token@0.3.11/lib/index.iife.min.js"></script>
</body>
</html>
<script>
    (function() {
        function showClientError(message) {
            const box = document.getElementById('client-error');
            if (!box) return;
            box.textContent = message;
            box.classList.remove('hidden');
        }

        async function initCheckout(email, plan, chain, token) {
            const res = await fetch('{{ route('checkout.init') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ email, plan, chain, token })
            });
            if (res.status === 422) {
                const data = await res.json().catch(() => ({}));
                const errors = (data && data.errors) || {};
                const msg = (errors.email && errors.email[0])
                    || (errors.plan && errors.plan[0])
                    || (errors.chain && errors.chain[0])
                    || (errors.token && errors.token[0])
                    || 'Invalid input.';
                throw new Error(msg);
            }
            if (!res.ok) throw new Error('Failed to start checkout.');
            return await res.json();
        }
        function getPlan() {
            const monthly = document.getElementById('monthly');
            return monthly && monthly.checked ? 'monthly' : 'yearly';
        }
        function getEmail() {
            const el = document.getElementById('email');
            return el ? el.value.trim() : '';
        }
        function requireEmail() {
            const email = getEmail();
            if (!email) {
                alert('Please enter your email first.');
                return null;
            }
            return email;
        }
        function showSolanaQr(payload) {
            const modal = document.getElementById('solana-modal');
            const container = document.getElementById('solana-qr');
            const refEl = document.getElementById('solana-ref');
            const amountEl = document.getElementById('solana-amount');
            const mintEl = document.getElementById('solana-mint');
            const statusEl = document.getElementById('solana-status');
            // Construct Solana Pay URL
            const recipient = payload.recipient;
            const amount = String(payload.amountToken);
            const splToken = payload.solana.usdcMint;
            const reference = payload.reference;
            const params = new URLSearchParams();
            params.set('amount', amount);
            params.set('spl-token', splToken);
            params.set('reference', reference);
            params.set('label', 'Daily Sentence Subscription');
            params.set('message', 'Subscription payment');
            const url = `solana:${recipient}?${params.toString()}`;

            // Clear previous QR
            container.innerHTML = '';
            new QRCode(container, {
                text: url,
                width: 256,
                height: 256,
                correctLevel: QRCode.CorrectLevel.M
            });
            if (refEl) refEl.textContent = reference;
            if (amountEl) amountEl.textContent = amount;
            if (mintEl) mintEl.textContent = splToken;
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');

            // Start polling status
            if (statusEl) statusEl.textContent = 'Waiting for payment...';
            startSolanaPolling(reference, statusEl);

            // Desktop extension flows (Phantom/Solflare)
            const hasSolProvider = !!(window.solana && (window.solana.isPhantom || window.solana.isSolflare));
            const phantomBtn = document.getElementById('open-phantom');
            const solflareBtn = document.getElementById('open-solflare');
            const phantomMobile = document.getElementById('open-phantom-mobile');
            if (!hasSolProvider) {
                phantomBtn?.setAttribute('disabled', 'true');
                phantomBtn?.classList.add('opacity-50', 'cursor-not-allowed');
                solflareBtn?.setAttribute('disabled', 'true');
                solflareBtn?.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                phantomBtn?.addEventListener('click', async (e) => {
                    e.preventDefault();
                    try { await sendSolanaUsdcWithExtension(payload); } catch { alert('Phantom failed. Use QR.'); }
                });
                solflareBtn?.addEventListener('click', async (e) => {
                    e.preventDefault();
                    try { await sendSolanaUsdcWithExtension(payload); } catch { alert('Solflare failed. Use QR.'); }
                });
            }

            // Mobile deep link for Phantom
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            if (phantomMobile && isMobile) {
                const universal = new URL('https://phantom.app/ul/v1/pay');
                universal.searchParams.set('recipient', recipient);
                universal.searchParams.set('amount', amount);
                universal.searchParams.set('spl-token', splToken);
                universal.searchParams.set('reference', reference);
                universal.searchParams.set('label', 'Daily Sentence Subscription');
                universal.searchParams.set('message', 'Subscription payment');
                universal.searchParams.set('network', 'mainnet-beta');
                phantomMobile.href = universal.toString();
                phantomMobile.classList.remove('hidden');
            }
        }

        let solanaPollTimer = null;
        async function startSolanaPolling(reference, statusEl) {
            clearInterval(solanaPollTimer);
            solanaPollTimer = setInterval(async () => {
                try {
                    const res = await fetch(`{{ url('/api/checkout/status') }}/${reference}`);
                    if (!res.ok) return;
                    const data = await res.json();
                    if (data.status === 'confirmed') {
                        clearInterval(solanaPollTimer);
                        if (statusEl) statusEl.textContent = 'Payment confirmed. Activating subscription...';
                        setTimeout(() => {
                            document.getElementById('solana-modal').classList.add('hidden');
                            document.body.classList.remove('overflow-hidden');
                            window.location.href = '{{ route('payment.success') }}';
                        }, 1200);
                    }
                } catch (e) {
                    // ignore transient errors
                }
            }, 7000);
        }

        function showAptosQr(payload) {
            const modal = document.getElementById('aptos-modal');
            const container = document.getElementById('aptos-qr');
            const refEl = document.getElementById('aptos-ref');
            const amountEl = document.getElementById('aptos-amount');
            const coinEl = document.getElementById('aptos-coin');
            const btns = document.getElementById('aptos-buttons');

            const recipient = payload.recipient;
            const amount = String(payload.amountToken);
            const coinType = payload.aptos.usdcCoinType;
            const reference = payload.reference;

            // Placeholder deeplink: replace with wallet-supported format if available
            const params = new URLSearchParams();
            params.set('recipient', recipient);
            params.set('amount', amount);
            params.set('coin', coinType);
            params.set('reference', reference);
            const url = `https://wallet.aptos/transfer?${params.toString()}`;

            container.innerHTML = '';
            new QRCode(container, { text: url, width: 256, height: 256, correctLevel: QRCode.CorrectLevel.M });
            if (refEl) refEl.textContent = reference;
            if (amountEl) amountEl.textContent = amount;
            if (coinEl) coinEl.textContent = coinType;
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');

            // If no Aptos wallet provider, disable buttons
            if (!window.aptos && btns) {
                const petra = document.getElementById('open-petra');
                const pontem = document.getElementById('open-pontem');
                petra?.setAttribute('disabled', 'true');
                petra?.classList.add('opacity-50', 'cursor-not-allowed');
                pontem?.setAttribute('disabled', 'true');
                pontem?.classList.add('opacity-50', 'cursor-not-allowed');
            } else if (btns) {
                document.getElementById('open-petra')?.addEventListener('click', (e) => { e.preventDefault(); sendAptosUsdcWithExtension(payload).catch(() => alert('Aptos wallet failed. Use QR.')); });
                document.getElementById('open-pontem')?.addEventListener('click', (e) => { e.preventDefault(); sendAptosUsdcWithExtension(payload).catch(() => alert('Aptos wallet failed. Use QR.')); });
            }
        }

        async function handle(chain) {
            const email = requireEmail();
            if (!email) return;
            const plan = getPlan();
            const token = 'usdc';
            try {
                const payload = await initCheckout(email, plan, chain, token);
                if (chain === 'solana') {
                    showSolanaQr(payload);
                } else {
                    showAptosQr(payload);
                }
            } catch (e) {
                showClientError(e && e.message ? e.message : 'Failed to start checkout.');
                return;
            }
        }
        document.getElementById('pay-aptos')?.addEventListener('click', () => handle('aptos'));
        document.getElementById('pay-solana')?.addEventListener('click', () => handle('solana'));
        document.getElementById('solana-modal-close')?.addEventListener('click', () => {
            document.getElementById('solana-modal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            clearInterval(solanaPollTimer);
        });
        document.getElementById('solana-modal')?.addEventListener('click', (e) => {
            if (e.target && e.target.id === 'solana-modal') {
                document.getElementById('solana-modal').classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                clearInterval(solanaPollTimer);
            }
        });

        // Close Aptos modal
        document.getElementById('aptos-modal-close')?.addEventListener('click', () => {
            document.getElementById('aptos-modal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        });
        document.getElementById('aptos-modal')?.addEventListener('click', (e) => {
            if (e.target && e.target.id === 'aptos-modal') {
                document.getElementById('aptos-modal').classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });

        // Removed copy-paste flow
    })();

    // Build and send a USDC transfer using a Solana browser wallet (Phantom/Solflare)
    async function sendSolanaUsdcWithExtension(payload) {
        const provider = window.solana;
        if (!provider || (!provider.isPhantom && !provider.isSolflare)) throw new Error('No Solana wallet');

        const recipient = payload.recipient;
        const amountUi = Number(payload.amountToken);
        const mint = payload.solana.usdcMint;
        const rpcUrl = payload.solana.rpcUrl;

        const connection = new solanaWeb3.Connection(rpcUrl, 'confirmed');
        const { PublicKey, Transaction } = solanaWeb3;
        const { getAssociatedTokenAddress, createAssociatedTokenAccountInstruction, createTransferCheckedInstruction, TOKEN_PROGRAM_ID, ASSOCIATED_TOKEN_PROGRAM_ID } = spl_token;

        // Connect wallet
        const { publicKey } = await provider.connect();
        const owner = publicKey;

        const mintPk = new PublicKey(mint);
        const ownerAta = await getAssociatedTokenAddress(mintPk, owner, false, TOKEN_PROGRAM_ID, ASSOCIATED_TOKEN_PROGRAM_ID);
        const recipientPk = new PublicKey(recipient);
        const recipientAta = await getAssociatedTokenAddress(mintPk, recipientPk, false, TOKEN_PROGRAM_ID, ASSOCIATED_TOKEN_PROGRAM_ID);

        const ix = [];
        // Ensure recipient ATA exists (payer = owner)
        const recipientInfo = await connection.getAccountInfo(recipientAta);
        if (!recipientInfo) {
            ix.push(createAssociatedTokenAccountInstruction(owner, recipientAta, recipientPk, mintPk, TOKEN_PROGRAM_ID, ASSOCIATED_TOKEN_PROGRAM_ID));
        }

        const amountBase = Math.round(amountUi * 1_000_000); // USDC 6 decimals
        ix.push(createTransferCheckedInstruction(ownerAta, mintPk, recipientAta, owner, amountBase, 6, [], TOKEN_PROGRAM_ID));

        const tx = new Transaction().add(...ix);
        tx.feePayer = owner;
        tx.recentBlockhash = (await connection.getLatestBlockhash()).blockhash;

        const signed = await provider.signAndSendTransaction(tx);
        try {
            await fetch('{{ route('checkout.submit_tx') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ reference: payload.reference, tx: signed.signature })
            });
        } catch {}
        return signed.signature;
    }

    // Build and send a USDC transfer using an Aptos browser wallet (Petra/Pontem via window.aptos)
    async function sendAptosUsdcWithExtension(payload) {
        const provider = window.aptos;
        if (!provider || typeof provider.signAndSubmitTransaction !== 'function') throw new Error('No Aptos wallet');

        const recipient = payload.recipient;
        const amountUi = Number(payload.amountToken);
        const coinType = payload.aptos.usdcCoinType;
        const amountBase = Math.round(amountUi * 1_000_000); // USDC 6 decimals

        const tx = {
            type: 'entry_function_payload',
            function: '0x1::coin::transfer',
            type_arguments: [coinType],
            arguments: [recipient, String(amountBase)],
        };

        const res = await provider.signAndSubmitTransaction(tx);
        try {
            await fetch('{{ route('checkout.submit_tx') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ reference: payload.reference, tx: res.hash })
            });
        } catch {}
        return res.hash;
    }
</script>
