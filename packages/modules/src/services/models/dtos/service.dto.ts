export type ServiceDto = {
    id: string;
    activity_id: string;
    animal_type_id: string;
    name: string;
    description: string | null;
    is_included: boolean;
    price: string | null;
};
