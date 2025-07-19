import {getRateCards} from "../../.server/services/settings.js";

const BillingCardForm = async ({ request }) => {
    const {primary_rate_card, secondary_rate_card, rate_cards} = await getRateCards({ request });

    return {
        title: '',
        action: '',
        actionTitle: 'Save',
        encType: '',
        method: 'post',
        fields: [
            {
                name: "primary_rate_card_id",
                value: primary_rate_card?.id,
                title: "Primary Rate Card",
                description: "",
                options: rate_cards,
                type: "select",
                required: true
            },
            {
                name: "secondary_rate_card_id",
                value: secondary_rate_card?.id,
                title: "Secondary Rate Card",
                description: "",
                options: rate_cards,
                type: "select",
                required: true
            },
        ]
    };
};

export default BillingCardForm;
