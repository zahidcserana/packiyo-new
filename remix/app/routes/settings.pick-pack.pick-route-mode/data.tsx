import {getCustomerSettings, getStaticCustomerData} from "../../.server/services/settings.js";
import {getIdByKey, getValueByKey} from "../../helpers/helpers";

const PickingRouteForm = async ({ request }) => {
    const settings = await getCustomerSettings({ request });
    const {picking_route_strategies} = await getStaticCustomerData({ request });

    return {
        title: '',
        action: '',
        actionTitle: 'Save',
        encType: 'multipart/form-data',
        method: 'post',
        fields: [
            {
                name: `customer_settings[${getIdByKey(settings, 'picking_route_strategy')}][picking_route_strategy]`,
                value: getValueByKey(settings, "picking_route_strategy"),
                title: "Picking Route Strategy",
                description: "",
                options: picking_route_strategies,
                type: "select",
                required: true
            }
        ]
    };
};

export default PickingRouteForm;
