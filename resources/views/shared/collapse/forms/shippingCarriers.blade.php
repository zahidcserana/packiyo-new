<div class="row border-12  py-0 py-md-3 p-3 m-0 mb-3 bg-white collapse select2Container" id="toggleFilterForm">
    <div class="col-12 col-md-12 p-0">
        <form class="" id="">
            <div class="row">
                <div class="col-12 col-md-12">
                    <div class="row">
                        <div class="form-group col-12 col-md-6">
                            <label for="" class="font-xs">{{ __('Carrier') }}</label>
                            <select name="name" class="form-control">
                                <option value="">{{ __('All') }}</option>
                                @foreach($data['carriers'] ?? [] as $carrier)
                                    <option value="{{$carrier}}">
                                        {{ $carrier }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6 d-flex justify-content-end align-items-start">
                            <button type="submit" class="btn bg-logoOrange text-white mr-4 mt-30px" id="submitFilterButton">Filter</button>
                            <button id="resetFilter" class="btn bg-logoOrange text-white mt-30px">Reset</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

