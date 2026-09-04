@php
    use App\Models\ApplicationSetting;
    use Illuminate\Support\Facades\Storage;

    $settings = ApplicationSetting::current();
    $organizationName = trim((string) $settings->organization_name);

    if ($organizationName === '' || $organizationName === 'Μητρώο Παγίων') {
        $organizationName = 'Επωνυμία Φορέα';
    }

    $logoDataUri = null;

    if (filled($settings->logo_path) && Storage::disk('local')->exists($settings->logo_path)) {
        $mime = Storage::disk('local')->mimeType($settings->logo_path) ?: 'image/png';
        $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode(
            Storage::disk('local')->get($settings->logo_path),
        );
    }
@endphp

<div class="application-brand">
    <img
        class="application-brand__logo"
        src="{{ $logoDataUri ?: asset('pwa-icon.svg') }}"
        alt="Λογότυπο {{ $organizationName }}"
    >

    <span class="application-brand__text">
        <strong>{{ $organizationName }}</strong>
        <span class="application-brand__product">- Μητρώο Παγίων</span>
    </span>
</div>

<style>
    .application-brand {
        display: flex;
        align-items: center;
        min-width: 0;
        max-width: 100%;
        height: 3.5rem;
        gap: .7rem;
    }

    .application-brand__logo {
        flex: 0 0 auto;
        width: auto;
        max-width: 6rem;
        height: 100%;
        max-height: 3.5rem;
        border-radius: .3rem;
        object-fit: contain;
        object-position: left center;
    }

    .application-brand__text {
        display: flex;
        align-items: baseline;
        flex-wrap: wrap;
        min-width: 0;
        column-gap: .4rem;
        row-gap: .05rem;
        color: rgb(17 24 39);
        font-size: 1.1rem;
        line-height: 1.15;
        letter-spacing: -.012em;
    }

    .application-brand__text strong {
        overflow: hidden;
        min-width: 0;
        max-width: 13rem;
        text-overflow: ellipsis;
        font-size: 1.15rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .application-brand__product {
        color: rgb(75 85 99);
        font-size: 1.05rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .dark .application-brand__text {
        color: rgb(250 250 250);
    }

    .dark .application-brand__product {
        color: rgb(212 212 216);
    }

    @media (max-width: 420px) {
        .application-brand__text {
            align-items: flex-start;
            flex-direction: column;
            gap: .1rem;
            font-size: .92rem;
            white-space: normal;
        }

        .application-brand__text strong {
            max-width: 11rem;
            font-size: .98rem;
        }

        .application-brand__product {
            font-size: .9rem;
        }
    }
</style>
