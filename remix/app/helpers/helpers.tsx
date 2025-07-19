import { redirect } from "@remix-run/react";
import FileInput from "../components/Form/Input/FileInput";
import HiddenInput from "../components/Form/Input/HiddenInput";
import SelectInput from "../components/Form/Input/SelectInput";
import TextInput from "../components/Form/Input/TextInput";
import ToggleInput from "../components/Form/Input/ToggleInput";
import Textarea from "../components/Form/Textarea/Textarea";

export const prepareSuccessResponse = (data) => {
    return { success: data?.success ?? true, message: data?.message ?? 'Data saved.', data };
}

export const prepareErrorResponse = (error) => {
    if (error.isAuthError) {
        throw redirect('/settings')
    }

    const data = error?.response?.data;

    if (data) {
        const messages = [];

        for (const field in data.errors) {
            messages.push(data.errors[field]?.detail);
        }

        const message = messages.join("\n");

        if (message) {
            return { success: false, message: message };
        }
    }

    if (data?.message) {
        return { success: false, message: data.message };
    }

    return { success: false, message: 'Something went wrong.' };
}

export const prepareFormData = (formData, type) => {
    const id = formData.get('id');

    const formDataObject = {};

    for (const pair of formData.entries()) {
        const [name, value] = pair;

        if (name === 'id') {
            continue;
        }

        if (name !== 'formType') {
            formDataObject[name] = value;
        }
    }

    return {
        data: {
            type,
            ...(id && { id }),
            attributes: formDataObject,
        },
    };
};

export const getValueByKey = (settings, key) => settings.find(setting => setting.key === key)?.value;
export const getIdByKey = (settings, key) => settings.find(setting => setting.key === key)?.setting_id;

export const renderField = (field, empty = false) => {
    if (field.type === 'toggle' || field.type === 'checkbox') {
        return (
            <div key={field.name}>
                <ToggleInput
                    name={field.name}
                    title={field.title}
                    required={field.required}
                    description={field.description}
                    initialValue={empty ? '' : field.value}
                    inverted={field.inverted}
                />
            </div>
        );
    } else if (field.type === 'select') {
        return (
            <div key={field.name}>
                <SelectInput
                    name={field.name}
                    title={field.title}
                    required={field.required}
                    description={field.description}
                    options={field.options}
                    value={empty ? '' : field.value}
                />
            </div>
        );
    } else if (field.type === 'file') {
        return (
            <div key={field.name}>
                <FileInput
                    name={field.name}
                    title={field.title}
                    required={field.required}
                    description={field.description}
                    value={empty ? '' : field.value}
                    id={field.id}
                />
            </div>
        );
    } else if (field.type === 'textarea') {
        return (
            <div key={field.name}>
                <Textarea
                    name={field.name}
                    title={field.title}
                    required={field.required}
                    description={field.description}
                    value={empty ? '' : field.value}
                />
            </div>
        );
    } else if (field.type === 'hidden') {
        return (
            <div key={field.name}>
                <HiddenInput
                    name={field.name}
                    required={field.required}
                    value={empty ? '' : field.value}
                />
            </div>
        );
    } else if (field.type === 'divider') {
        return (
            <hr key={Math.random()} className="hr mb-4"/>
        );
    } else if (field.type === 'description') {
        return (
            <div key={Math.random()} className="mb-4" dangerouslySetInnerHTML={{ __html: field.value }} />
        );
    } else if (field.type === 'text' || field.type === 'string' || field.type === 'number' || field.type === 'date') {
        return (
            <div key={field.name}>
                <TextInput
                    name={field.name}
                    title={field.title}
                    required={field.required}
                    type={field.type}
                    description={field.description}
                    value={empty ? '' : field.value}
                />
            </div>
        );
    }
};

export const redirectBack = (request) => {
    return redirect(request.headers.get("Referer"));
}

export const dateMinusDays = (days) => {
    const currentDate = new Date();
    currentDate.setDate(currentDate.getDate() - days);
    return currentDate.toISOString().split('T')[0];
}

export const formatDate = (date) => {
    const dateObject = new Date(date);

    return dateObject.toISOString().replace('T', ' ').split('.')[0];
}
