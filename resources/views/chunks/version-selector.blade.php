@if(!empty($versions))
    <form class="form-group row version-selector">
        <label for="component-version" class="col-sm-4 col-form-label">{{ __('Version') }}</label>
        <div class="col-sm-8">
            <select id="component-version" class="form-control ds-input">
                @foreach($versions as $version)
                    <option value="{{ $version['link'] }}" {{ $version['selected'] ? 'selected' : '' }}>
                        {{ $version['title'] }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>
@endif

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('component-version').onchange = function (event) {
                window.location.href = event.target.value;
            }
        }, false);
    </script>
@endpush
