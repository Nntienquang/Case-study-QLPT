(function () {
    const API_URL = 'https://provinces.open-api.vn/api/v1/?depth=3';

    function setOptions(select, options, placeholder, selectedCode) {
        select.innerHTML = `<option value="">${placeholder}</option>`;
        options.forEach((item) => {
            const option = document.createElement('option');
            option.value = String(item.code);
            option.textContent = item.name;
            option.dataset.name = item.name;
            option.selected = selectedCode && String(item.code) === String(selectedCode);
            select.appendChild(option);
        });
        select.disabled = false;
    }

    function selectedName(select) {
        return select.selectedOptions[0]?.dataset.name || '';
    }

    function updateHidden(select, hidden) {
        if (hidden) {
            hidden.value = selectedName(select);
        }
    }

    function updateFullAddress(root) {
        const street = root.querySelector('[data-address-street]')?.value.trim() || '';
        const ward = root.querySelector('[data-address-ward-name]')?.value.trim() || '';
        const district = root.querySelector('[data-address-district-name]')?.value.trim() || '';
        const province = root.querySelector('[data-address-province-name]')?.value.trim() || '';
        const fullAddress = root.querySelector('[data-address-full]');

        if (fullAddress) {
            fullAddress.value = [street, ward, district, province].filter(Boolean).join(', ');
        }
    }

    function updateStatus(root, message, isError) {
        const status = root.querySelector('[data-address-status]');
        if (!status) return;
        status.textContent = message || '';
        status.classList.toggle('text-danger', Boolean(isError));
        status.classList.toggle('text-muted', !isError);
    }

    async function initAddressPicker(root) {
        const provinceSelect = root.querySelector('[data-address-province]');
        const districtSelect = root.querySelector('[data-address-district]');
        const wardSelect = root.querySelector('[data-address-ward]');
        const provinceName = root.querySelector('[data-address-province-name]');
        const districtName = root.querySelector('[data-address-district-name]');
        const wardName = root.querySelector('[data-address-ward-name]');
        const streetInput = root.querySelector('[data-address-street]');

        if (!provinceSelect || !districtSelect || !wardSelect) return;

        const selectedProvince = provinceSelect.dataset.selected || '';
        const selectedDistrict = districtSelect.dataset.selected || '';
        const selectedWard = wardSelect.dataset.selected || '';

        provinceSelect.disabled = true;
        districtSelect.disabled = true;
        wardSelect.disabled = true;
        updateStatus(root, 'Đang tải dữ liệu tỉnh/thành, quận/huyện, phường/xã...');

        try {
            const response = await fetch(API_URL, { cache: 'force-cache' });
            if (!response.ok) throw new Error('Address API unavailable');

            const provinces = await response.json();
            setOptions(provinceSelect, provinces, '-- Chọn tỉnh/thành --', selectedProvince);

            function populateDistricts() {
                const province = provinces.find((item) => String(item.code) === provinceSelect.value);
                updateHidden(provinceSelect, provinceName);
                setOptions(districtSelect, province?.districts || [], '-- Chọn quận/huyện --', selectedDistrict);
                populateWards();
            }

            function populateWards() {
                const province = provinces.find((item) => String(item.code) === provinceSelect.value);
                const district = province?.districts?.find((item) => String(item.code) === districtSelect.value);
                updateHidden(districtSelect, districtName);
                setOptions(wardSelect, district?.wards || [], '-- Chọn phường/xã --', selectedWard);
                updateHidden(wardSelect, wardName);
                updateFullAddress(root);
            }

            provinceSelect.addEventListener('change', () => {
                districtSelect.dataset.selected = '';
                wardSelect.dataset.selected = '';
                populateDistricts();
            });
            districtSelect.addEventListener('change', () => {
                wardSelect.dataset.selected = '';
                populateWards();
            });
            wardSelect.addEventListener('change', () => {
                updateHidden(wardSelect, wardName);
                updateFullAddress(root);
            });
            streetInput?.addEventListener('input', () => updateFullAddress(root));

            if (selectedProvince) {
                populateDistricts();
                districtSelect.value = selectedDistrict;
                populateWards();
                wardSelect.value = selectedWard;
                updateHidden(wardSelect, wardName);
            }

            updateFullAddress(root);
            updateStatus(root, 'Dữ liệu địa chỉ lấy từ provinces.open-api.vn. Bạn vẫn có thể ghim lại tọa độ trên bản đồ.');
        } catch (error) {
            provinceSelect.disabled = false;
            districtSelect.disabled = false;
            wardSelect.disabled = false;
            updateStatus(root, 'Không tải được API địa chỉ. Bạn có thể nhập địa chỉ chi tiết thủ công rồi ghim bản đồ.', true);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-address-picker]').forEach(initAddressPicker);
    });
})();
