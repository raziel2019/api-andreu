<x-admin.layout title="Configuració">
    <h1>Configuració</h1>
    <p>Deixa en blanc les dades de contacte que el client encara no ha confirmat.</p>
    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <fieldset><legend>Dades de contacte</legend>
            <x-admin.field name="contact_email" label="Correu de contacte" type="email" :value="$settings->contact_email" />
            <x-admin.field name="phone" label="Telèfon" :value="$settings->phone" />
            <x-admin.field name="linkedin_url" label="URL de LinkedIn" type="url" :value="$settings->linkedin_url" />
            <x-admin.field name="instagram_url" label="URL d’Instagram" type="url" :value="$settings->instagram_url" />
            <x-admin.translated-field name="location" label="Ubicació" :value="$settings->location" :required="false" />
        </fieldset>
        <fieldset><legend>Identitat legal (pendent de confirmació)</legend>
            <p>Deixa en blanc les dades desconegudes. La ubicació pública no substitueix una adreça postal. Si el responsable coincideix amb el titular, reutilitza el nom legal en redactar el text. El correu general i el telèfon s’editen més amunt.</p>
            <x-admin.field name="legal_name" label="Nom legal / raó social" :value="$settings->legal_name" />
            <x-admin.field name="tax_identifier" label="NIF / identificador fiscal" :value="$settings->tax_identifier" />
            <x-admin.field name="postal_address" label="Adreça postal" :value="$settings->postal_address" />
            <x-admin.field name="privacy_contact_email" label="Correu de privacitat (si és diferent)" :value="$settings->privacy_contact_email" type="email" />
            <x-admin.field name="professional_registration" label="Dades professionals / registre (si escau)" :value="$settings->professional_registration" />
            <x-admin.field name="data_controller_name" label="Responsable del tractament (si és diferent)" :value="$settings->data_controller_name" />
        </fieldset>
        <x-admin.image-field name="logo" label="Logotip" :path="$settings->logo_path" />
        <h2>Crida de contacte compartida</h2>
        <p>Aquest contingut és compartit entre les pàgines. La crida pròpia de l’inici s’edita a Inicio.</p>
        @foreach(['callout_title' => 'Títol', 'callout_content' => 'Contingut', 'callout_label' => 'Text del botó'] as $field => $label)
            <x-admin.translated-field :name="$field" :label="$label" :value="$settings->$field" :multiline="$field === 'callout_content'" />
        @endforeach
        <x-admin.field name="callout_destination" label="Destinació del botó de contacte" :value="$settings->callout_destination" />
        <h2>Peu de pàgina</h2>
        @foreach(['footer_description' => 'Descripció', 'footer_cta_label' => 'Text del botó', 'footer_copyright' => 'Text de drets d’autor'] as $field => $label)
            <x-admin.translated-field :name="$field" :label="$label" :value="$settings->$field" :multiline="$field === 'footer_description'" />
        @endforeach
        <x-admin.field name="footer_cta_destination" label="Destinació del botó del peu de pàgina" :value="$settings->footer_cta_destination" />
        <p><button type="submit">Desa la configuració</button></p>
    </form>
</x-admin.layout>
