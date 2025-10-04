(function () {
    if ( typeof window.andwSctData === 'undefined' ) {
        return;
    }

    const { __ } = window.wp?.i18n || { __: ( text ) => text };

    document.addEventListener( 'click', async ( event ) => {
        const button = event.target.closest( '.andw-sct-button' );
        if ( ! button ) {
            return;
        }

        event.preventDefault();

        const container = button.closest( '.andw-sct-checkout' );
        if ( ! container ) {
            return;
        }

        if ( button.dataset.processing === 'true' ) {
            return;
        }

        const messageEl = container.querySelector( '.andw-sct-message' );
        const setMessage = ( text, isError = true ) => {
            if ( messageEl ) {
                messageEl.textContent = text || '';
                messageEl.classList.toggle( 'andw-sct-message--error', isError );
            }
        };

        if ( container.dataset.requireLogin === 'true' && ! window.andwSctData.isLoggedIn ) {
            setMessage( window.andwSctData.messages.loginRequired || __( 'ログインが必要です。', 'andw-sct' ) );
            return;
        }

        if ( window.andwSctData.consent.enabled ) {
            const consentInput = container.querySelector( '.andw-sct-consent__input' );
            if ( consentInput && ! consentInput.checked ) {
                setMessage( window.andwSctData.consent.message || __( '購入前に同意チェックを入れてください。', 'andw-sct' ) );
                return;
            }
        }

        const payload = {
            sku: container.dataset.sku || '',
            qty: parseInt( container.dataset.qty || '1', 10 ) || 1,
            label: button.dataset.label || '',
        };

        if ( container.dataset.successUrl ) {
            payload.success_url = container.dataset.successUrl;
        }
        if ( container.dataset.cancelUrl ) {
            payload.cancel_url = container.dataset.cancelUrl;
        }
        if ( container.dataset.caseId ) {
            payload.case_id = container.dataset.caseId;
        }

        try {
            button.dataset.processing = 'true';
            button.setAttribute( 'aria-busy', 'true' );
            button.disabled = true;
            setMessage( window.andwSctData.messages.processing || __( '処理中...', 'andw-sct' ), false );

            const response = await fetch( window.andwSctData.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Andw-Sct-Nonce': window.andwSctData.nonce,
                },
                body: JSON.stringify( payload ),
            } );

            const data = await response.json().catch( () => ( {} ) );

            if ( response.ok && data && data.success && data.data && data.data.url ) {
                window.location.href = data.data.url;
                return;
            }

            const errorMessage = ( data && data.data && data.data.message ) || window.andwSctData.messages.genericError || __( 'チェックアウトの開始に失敗しました。', 'andw-sct' );
            setMessage( errorMessage );
        } catch ( error ) {
            setMessage( window.andwSctData.messages.genericError || __( 'チェックアウトの開始に失敗しました。', 'andw-sct' ) );
        } finally {
            button.dataset.processing = 'false';
            button.removeAttribute( 'aria-busy' );
            button.disabled = false;
        }
    } );
})();
